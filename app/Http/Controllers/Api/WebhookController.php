<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Conversa;
use App\Models\Message;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $payload = $request->all();
            $event = $payload['event'] ?? null;
            $instanceName = $payload['instance'] ?? null;

            Log::info('Webhook recebido', ['event' => $event, 'instance' => $instanceName]);

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

            $updateData = [
                'is_connected' => $isConnected,
                'last_connection' => $isConnected ? now() : $account->last_connection,
            ];

            // Salvar owner_jid quando conectar (vem no payload da Evolution)
            if ($isConnected) {
                $ownerJid = $payload['data']['ownerJid']
                    ?? $payload['data']['jid']
                    ?? $payload['data']['instance']['owner']
                    ?? null;

                if ($ownerJid) {
                    $updateData['owner_jid'] = $ownerJid;
                }
            }

            $account->update($updateData);

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
            $this->processMessage($account, $data);
            return response()->json(['status' => 'ok', 'processed' => 1]);
        } else {
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
        $participant = $key['participant'] ?? null; // JID do remetente em grupos

        if (!$remoteJid || !$messageId) {
            return;
        }

        // Verificar se é grupo
        $isGroup = str_contains($remoteJid, '@g.us');

        // Nome do remetente
        $senderName = $messageData['pushName'] ?? null;

        // Buscar ou criar chat
        $chat = Chat::firstOrCreate(
            ['account_id' => $account->id, 'chat_id' => $remoteJid],
            [
                'chat_name' => $isGroup
                    ? ($this->getGroupName($messageData) ?? $this->extractPhoneFromJid($remoteJid))
                    : ($senderName ?? $this->extractPhoneFromJid($remoteJid)),
                'chat_type' => $isGroup ? 'group' : 'individual',
            ]
        );

        // Para grupos, atualizar nome se veio no payload
        if ($isGroup && isset($messageData['groupSubject'])) {
            $chat->update(['chat_name' => $messageData['groupSubject']]);
        }

        // Determinar tipo e conteúdo da mensagem
        $messageType = 'text';
        $messageText = null;
        $mediaUrl = null;
        $mediaMimeType = null;
        $mediaFilename = null;
        $mediaDuration = null;
        $quotedMessageId = null;
        $quotedText = null;

        // Texto simples
        if (isset($message['conversation'])) {
            $messageText = $message['conversation'];
        } elseif (isset($message['extendedTextMessage'])) {
            $messageText = $message['extendedTextMessage']['text'] ?? null;
            // Verificar se tem mensagem citada
            if (isset($message['extendedTextMessage']['contextInfo']['quotedMessage'])) {
                $quotedMessageId = $message['extendedTextMessage']['contextInfo']['stanzaId'] ?? null;
                $quotedText = $this->extractQuotedText($message['extendedTextMessage']['contextInfo']['quotedMessage']);
            }
        }
        // Imagem
        elseif (isset($message['imageMessage'])) {
            $messageType = 'image';
            $messageText = $message['imageMessage']['caption'] ?? null;
            $mediaMimeType = $message['imageMessage']['mimetype'] ?? 'image/jpeg';
            $mediaUrl = $this->downloadAndSaveMedia($account, $messageId, 'image', $mediaMimeType, $messageData);
            if (isset($message['imageMessage']['contextInfo']['quotedMessage'])) {
                $quotedMessageId = $message['imageMessage']['contextInfo']['stanzaId'] ?? null;
            }
        }
        // Vídeo
        elseif (isset($message['videoMessage'])) {
            $messageType = 'video';
            $messageText = $message['videoMessage']['caption'] ?? null;
            $mediaMimeType = $message['videoMessage']['mimetype'] ?? 'video/mp4';
            $mediaDuration = $message['videoMessage']['seconds'] ?? null;
            $mediaUrl = $this->downloadAndSaveMedia($account, $messageId, 'video', $mediaMimeType, $messageData);
        }
        // Áudio
        elseif (isset($message['audioMessage'])) {
            $messageType = 'audio';
            $mediaMimeType = $message['audioMessage']['mimetype'] ?? 'audio/ogg';
            $mediaDuration = $message['audioMessage']['seconds'] ?? null;
            $mediaUrl = $this->downloadAndSaveMedia($account, $messageId, 'audio', $mediaMimeType, $messageData);
        }
        // Documento
        elseif (isset($message['documentMessage'])) {
            $messageType = 'document';
            $mediaFilename = $message['documentMessage']['fileName'] ?? 'documento';
            $messageText = $mediaFilename;
            $mediaMimeType = $message['documentMessage']['mimetype'] ?? 'application/octet-stream';
            $mediaUrl = $this->downloadAndSaveMedia($account, $messageId, 'document', $mediaMimeType, $messageData, $mediaFilename);
        }
        // Sticker
        elseif (isset($message['stickerMessage'])) {
            $messageType = 'sticker';
            $mediaMimeType = $message['stickerMessage']['mimetype'] ?? 'image/webp';
            $mediaUrl = $this->downloadAndSaveMedia($account, $messageId, 'sticker', $mediaMimeType, $messageData);
        }
        // Localização
        elseif (isset($message['locationMessage'])) {
            $messageType = 'location';
        }
        // Contato
        elseif (isset($message['contactMessage'])) {
            $messageType = 'contact';
            $messageText = $message['contactMessage']['displayName'] ?? 'Contato';
        }

        // Determinar JID do remetente real
        $senderJid = $fromMe
            ? $account->owner_jid
            : ($isGroup ? $participant : $remoteJid);

        // Criar ou atualizar mensagem
        $dbMessage = Message::updateOrCreate(
            ['message_key' => $messageId],
            [
                'chat_id' => $chat->id,
                'from_jid' => $senderJid ?? $remoteJid,
                'sender_name' => $fromMe ? null : $senderName,
                'participant_jid' => $isGroup ? $participant : null,
                'to_jid' => $fromMe ? $remoteJid : $account->owner_jid,
                'message_text' => $messageText,
                'message_type' => $messageType,
                'media_url' => $mediaUrl,
                'media_mime_type' => $mediaMimeType,
                'media_filename' => $mediaFilename,
                'media_duration' => $mediaDuration,
                'is_from_me' => $fromMe,
                'timestamp' => $messageData['messageTimestamp'] ?? time(),
                'status' => 'delivered',
                'quoted_message_id' => $quotedMessageId,
                'quoted_text' => $quotedText,
                'message_raw' => $messageData,
            ]
        );

        // Atualizar último timestamp do chat
        $chat->update([
            'last_message_timestamp' => $dbMessage->timestamp,
            'unread_count' => $fromMe ? 0 : $chat->unread_count + 1,
        ]);

        // Criar ou atualizar conversa na fila (mensagens recebidas)
        if (!$fromMe) {
            $this->updateConversaQueue($account, $chat, $remoteJid, $messageData, $isGroup);
        }

        // Broadcast mensagem em tempo real
        try {
            broadcast(new NewMessageReceived($dbMessage, $account->id, $chat->id))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Broadcast falhou', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Baixar mídia da Evolution API e salvar no storage
     */
    protected function downloadAndSaveMedia(
        WhatsappAccount $account,
        string $messageId,
        string $type,
        string $mimeType,
        array $messageData,
        ?string $filename = null
    ): ?string {
        try {
            $evolutionService = app(EvolutionApiService::class);

            // Tentar baixar mídia via Evolution API
            $result = $evolutionService->downloadMedia($account->session_name, $messageId);

            if (!$result['success'] || empty($result['data']['base64'])) {
                Log::warning('Mídia não disponível para download', ['messageId' => $messageId]);
                return null;
            }

            $base64 = $result['data']['base64'];
            $extension = $this->getExtensionFromMimeType($mimeType);

            // Gerar nome do arquivo
            if ($filename) {
                $savedFilename = $messageId . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            } else {
                $savedFilename = $messageId . '.' . $extension;
            }

            // Salvar no storage
            $path = "media/{$account->id}/{$type}/{$savedFilename}";
            Storage::disk('public')->put($path, base64_decode($base64));

            return Storage::url($path);
        } catch (\Exception $e) {
            Log::error('Erro ao baixar mídia', ['error' => $e->getMessage(), 'messageId' => $messageId]);
            return null;
        }
    }

    protected function getExtensionFromMimeType(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/3gpp' => '3gp',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        return $map[$mimeType] ?? 'bin';
    }

    protected function extractQuotedText(array $quotedMessage): ?string
    {
        if (isset($quotedMessage['conversation'])) {
            return $quotedMessage['conversation'];
        }
        if (isset($quotedMessage['extendedTextMessage']['text'])) {
            return $quotedMessage['extendedTextMessage']['text'];
        }
        if (isset($quotedMessage['imageMessage']['caption'])) {
            return '[Imagem] ' . $quotedMessage['imageMessage']['caption'];
        }
        if (isset($quotedMessage['imageMessage'])) {
            return '[Imagem]';
        }
        if (isset($quotedMessage['videoMessage'])) {
            return '[Vídeo]';
        }
        if (isset($quotedMessage['audioMessage'])) {
            return '[Áudio]';
        }
        if (isset($quotedMessage['documentMessage'])) {
            return '[Documento] ' . ($quotedMessage['documentMessage']['fileName'] ?? '');
        }
        return null;
    }

    protected function getGroupName(array $messageData): ?string
    {
        // Tenta extrair nome do grupo do payload
        return $messageData['groupMetadata']['subject'] ??
               $messageData['groupSubject'] ??
               null;
    }

    protected function updateConversaQueue(
        WhatsappAccount $account,
        Chat $chat,
        string $remoteJid,
        array $messageData,
        bool $isGroup = false
    ) {
        // Para grupos, usar o JID do grupo como número
        $phoneNumber = $this->extractPhoneFromJid($remoteJid);

        // Nome do cliente (para grupos, usar nome do grupo)
        $clienteName = $isGroup
            ? ($chat->chat_name ?? $messageData['groupSubject'] ?? 'Grupo')
            : ($messageData['pushName'] ?? null);

        $conversa = Conversa::where('chat_id', $chat->id)
            ->whereIn('status', ['aguardando', 'em_atendimento'])
            ->first();

        if (!$conversa) {
            Conversa::create([
                'cliente_numero' => $phoneNumber,
                'cliente_nome' => $clienteName,
                'chat_id' => $chat->id,
                'account_id' => $account->id,
                'status' => 'aguardando',
                'iniciada_em' => now(),
                'ultima_msg_em' => now(),
                'cliente_aguardando_desde' => now(),
            ]);
        } else {
            $updateData = ['ultima_msg_em' => now()];

            // Atualizar nome se mudou
            if ($clienteName && $conversa->cliente_nome !== $clienteName) {
                $updateData['cliente_nome'] = $clienteName;
            }

            if (!$conversa->cliente_aguardando_desde) {
                $updateData['cliente_aguardando_desde'] = now();
            }

            $conversa->update($updateData);
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
        // SEND_MESSAGE é disparado quando uma mensagem é enviada pelo WhatsApp Web
        // Precisamos salvar essas mensagens também
        $instanceName = $payload['instance'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$instanceName) {
            return response()->json(['status' => 'error', 'message' => 'Instance name missing']);
        }

        $account = WhatsappAccount::where('instance_name', $instanceName)->first();
        if (!$account) {
            return response()->json(['status' => 'error', 'message' => 'Account not found']);
        }

        // O payload de SEND_MESSAGE tem estrutura similar ao MESSAGES_UPSERT
        // mas a mensagem vem diretamente em data, não em data[]
        if (!empty($data['key']) && !empty($data['message'])) {
            $this->processMessage($account, $data);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handlePresenceUpdate(array $payload)
    {
        return response()->json(['status' => 'ok']);
    }

    protected function extractPhoneFromJid(string $jid): string
    {
        return explode('@', $jid)[0];
    }
}
