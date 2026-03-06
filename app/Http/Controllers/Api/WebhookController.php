<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Contact;
use App\Models\Conversa;
use App\Models\LogSistema;
use App\Models\Message;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $payload = $request->all();
            $event = $payload['event'] ?? null;
            $instanceName = $payload['instance'] ?? null;

            Log::info('Webhook recebido', ['event' => $event, 'instance' => $instanceName, 'payload' => $payload]);

            $eventNormalized = strtoupper(str_replace('.', '_', $event ?? ''));

        return match ($eventNormalized) {
            'QRCODE_UPDATED' => $this->handleQrCode($payload),
            'CONNECTION_UPDATE' => $this->handleConnectionUpdate($payload),
            'MESSAGES_UPSERT' => $this->handleMessagesUpsert($payload),
            'MESSAGES_UPDATE' => $this->handleMessagesUpdate($payload),
            'MESSAGES_DELETE' => $this->handleMessagesDelete($payload),
            'SEND_MESSAGE' => $this->handleSendMessage($payload),
            'PRESENCE_UPDATE' => $this->handlePresenceUpdate($payload),
            default => response()->json(['status' => 'ignored', 'event' => $event]),
        };
        } catch (\Exception $e) {
            Log::error('Webhook error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    protected function handleQrCode(array $payload)
    {
        $instanceName = $payload['instance'] ?? null;
        $qrcode = $payload['data']['qrcode']['base64'] ?? null;

        if ($instanceName && $qrcode) {
            // Pode emitir evento via broadcasting para atualizar QR code em tempo real
            Log::info('QR Code atualizado', ['instance' => $instanceName]);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleConnectionUpdate(array $payload)
    {
        $instanceName = $payload['instance'] ?? null;
        $state = $payload['data']['state'] ?? null;

        if (!$instanceName) {
            return response()->json(['status' => 'error', 'message' => 'Instance name missing']);
        }

        $account = WhatsappAccount::where('session_name', $instanceName)->first();

        if ($account) {
            $isConnected = $state === 'open';
            $account->update([
                'is_connected' => $isConnected,
                'last_connection' => $isConnected ? now() : $account->last_connection,
            ]);

            Log::info('Connection update', ['instance' => $instanceName, 'state' => $state]);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleMessagesUpsert(array $payload)
    {
        $instanceName = $payload['instance'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$instanceName) {
            return response()->json(['status' => 'error', 'message' => 'Instance name missing']);
        }

        $account = WhatsappAccount::where('session_name', $instanceName)->first();

        if (!$account) {
            return response()->json(['status' => 'error', 'message' => 'Account not found']);
        }

        // v1.7.x envia uma mensagem única, v2.x envia array
        if (isset($data['key'])) {
            // Formato v1.7.x - mensagem única
            $this->processMessage($account, $data);
            return response()->json(['status' => 'ok', 'processed' => 1]);
        } else {
            // Formato v2.x - array de mensagens
            foreach ($data as $messageData) {
                if (is_array($messageData)) {
                    $this->processMessage($account, $messageData);
                }
            }
            return response()->json(['status' => 'ok', 'processed' => count($data)]);
        }
    }

    protected function processMessage(WhatsappAccount $account, array $messageData)
    {
        $key = $messageData['key'] ?? [];
        $message = $messageData['message'] ?? [];

        $remoteJid = $key['remoteJid'] ?? null;
        $messageId = $key['id'] ?? null;
        $fromMe = $key['fromMe'] ?? false;

        if (!$remoteJid || !$messageId) {
            return;
        }

        // Buscar ou criar chat
        $chat = Chat::firstOrCreate(
            ['account_id' => $account->id, 'chat_id' => $remoteJid],
            [
                'chat_name' => $messageData['pushName'] ?? $this->extractPhoneFromJid($remoteJid),
                'chat_type' => str_contains($remoteJid, '@g.us') ? 'group' : 'individual',
            ]
        );

        // Determinar tipo e conteúdo da mensagem
        $messageType = 'text';
        $messageText = null;
        $mediaUrl = null;
        $mediaMimeType = null;

        if (isset($message['conversation'])) {
            $messageText = $message['conversation'];
        } elseif (isset($message['extendedTextMessage'])) {
            $messageText = $message['extendedTextMessage']['text'] ?? null;
        } elseif (isset($message['imageMessage'])) {
            $messageType = 'image';
            $messageText = $message['imageMessage']['caption'] ?? null;
            $mediaMimeType = $message['imageMessage']['mimetype'] ?? null;
        } elseif (isset($message['videoMessage'])) {
            $messageType = 'video';
            $messageText = $message['videoMessage']['caption'] ?? null;
            $mediaMimeType = $message['videoMessage']['mimetype'] ?? null;
        } elseif (isset($message['audioMessage'])) {
            $messageType = 'audio';
            $mediaMimeType = $message['audioMessage']['mimetype'] ?? null;
        } elseif (isset($message['documentMessage'])) {
            $messageType = 'document';
            $messageText = $message['documentMessage']['fileName'] ?? null;
            $mediaMimeType = $message['documentMessage']['mimetype'] ?? null;
        } elseif (isset($message['stickerMessage'])) {
            $messageType = 'sticker';
        } elseif (isset($message['locationMessage'])) {
            $messageType = 'location';
        } elseif (isset($message['contactMessage'])) {
            $messageType = 'contact';
        }

        // Criar ou atualizar mensagem
        $dbMessage = Message::updateOrCreate(
            ['message_key' => $messageId],
            [
                'chat_id' => $chat->id,
                'from_jid' => $fromMe ? $account->owner_jid : $remoteJid,
                'to_jid' => $fromMe ? $remoteJid : $account->owner_jid,
                'message_text' => $messageText,
                'message_type' => $messageType,
                'media_url' => $mediaUrl,
                'media_mime_type' => $mediaMimeType,
                'is_from_me' => $fromMe,
                'timestamp' => $messageData['messageTimestamp'] ?? time(),
                'status' => 'delivered',
                'message_raw' => $messageData,
            ]
        );

        // Atualizar último timestamp do chat
        $chat->update([
            'last_message_timestamp' => $dbMessage->timestamp,
            'unread_count' => $fromMe ? 0 : $chat->unread_count + 1,
        ]);

        // Criar ou atualizar conversa na fila (apenas para mensagens recebidas de chats individuais)
        if (!$fromMe && $chat->chat_type === 'individual') {
            $this->updateConversaQueue($account, $chat, $remoteJid, $messageData);
        }

        // Broadcast mensagem em tempo real
        broadcast(new NewMessageReceived($dbMessage, $account->id, $chat->id))->toOthers();
    }

    protected function updateConversaQueue(WhatsappAccount $account, Chat $chat, string $remoteJid, array $messageData)
    {
        $phoneNumber = $this->extractPhoneFromJid($remoteJid);

        $conversa = Conversa::where('chat_id', $chat->id)
            ->whereIn('status', ['aguardando', 'em_atendimento'])
            ->first();

        if (!$conversa) {
            // Criar nova conversa na fila
            Conversa::create([
                'cliente_numero' => $phoneNumber,
                'cliente_nome' => $messageData['pushName'] ?? null,
                'chat_id' => $chat->id,
                'account_id' => $account->id,
                'status' => 'aguardando',
                'iniciada_em' => now(),
                'ultima_msg_em' => now(),
                'cliente_aguardando_desde' => now(),
            ]);
        } else {
            // Atualizar conversa existente
            $conversa->update([
                'ultima_msg_em' => now(),
                'cliente_aguardando_desde' => $conversa->cliente_aguardando_desde ?? now(),
            ]);
        }
    }

    protected function handleMessagesUpdate(array $payload)
    {
        $updates = $payload['data'] ?? [];

        foreach ($updates as $update) {
            $messageId = $update['key']['id'] ?? null;
            $status = $update['update']['status'] ?? null;

            if ($messageId && $status) {
                $statusMap = [
                    1 => 'pending',
                    2 => 'sent',
                    3 => 'delivered',
                    4 => 'read',
                ];

                Message::where('message_key', $messageId)
                    ->update(['status' => $statusMap[$status] ?? 'sent']);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleMessagesDelete(array $payload)
    {
        $messageId = $payload['data']['key']['id'] ?? null;

        if ($messageId) {
            Message::where('message_key', $messageId)
                ->update(['is_deleted' => true]);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleSendMessage(array $payload)
    {
        // Mensagem enviada pelo sistema - já está registrada
        return response()->json(['status' => 'ok']);
    }

    protected function handlePresenceUpdate(array $payload)
    {
        // Atualização de presença (online/offline/typing)
        return response()->json(['status' => 'ok']);
    }

    protected function extractPhoneFromJid(string $jid): string
    {
        return explode('@', $jid)[0];
    }
}
