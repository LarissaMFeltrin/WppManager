<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Conversa;
use App\Models\Message;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Console\Command;

class SyncPendingChats extends Command
{
    protected $signature = 'whatsapp:sync-pending {--instance= : Nome da instância (opcional)}';
    protected $description = 'Sincroniza mensagens pendentes da Evolution API para a fila de atendimento';

    protected EvolutionApiService $api;

    public function __construct(EvolutionApiService $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    public function handle()
    {
        $instanceName = $this->option('instance');

        $query = WhatsappAccount::where('is_connected', true);
        if ($instanceName) {
            $query->where('session_name', $instanceName);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->error('Nenhuma conta WhatsApp conectada encontrada.');
            return 1;
        }

        foreach ($accounts as $account) {
            $this->info("Sincronizando instância: {$account->session_name}");
            $this->syncInstance($account);
        }

        $this->info('Sincronização concluída!');
        return 0;
    }

    protected function syncInstance(WhatsappAccount $account)
    {
        // Buscar mensagens recentes
        $result = $this->api->fetchMessages($account->session_name, '', 200);

        if (!$result['success']) {
            $this->error("  Erro ao buscar mensagens: " . ($result['error'] ?? 'Desconhecido'));
            return;
        }

        $messages = $result['data'] ?? [];
        $this->info("  Encontradas " . count($messages) . " mensagens");

        // Agrupar mensagens por chat e pegar a mais recente de cada
        $chatLastMessage = [];
        foreach ($messages as $messageData) {
            $key = $messageData['key'] ?? [];
            $remoteJid = $key['remoteJid'] ?? null;

            if (!$remoteJid || str_contains($remoteJid, '@g.us')) {
                continue; // Ignorar grupos
            }

            $timestamp = $messageData['messageTimestamp'] ?? 0;

            // Guardar apenas a mensagem mais recente de cada chat
            if (!isset($chatLastMessage[$remoteJid]) || $timestamp > $chatLastMessage[$remoteJid]['messageTimestamp']) {
                $chatLastMessage[$remoteJid] = $messageData;
            }
        }

        $this->info("  Chats individuais encontrados: " . count($chatLastMessage));

        $created = 0;
        foreach ($chatLastMessage as $remoteJid => $messageData) {
            $fromMe = $messageData['key']['fromMe'] ?? false;

            // Só criar conversa se a ÚLTIMA mensagem NÃO for nossa (cliente aguardando resposta)
            if (!$fromMe) {
                if ($this->processChat($account, $messageData)) {
                    $created++;
                }
            }
        }

        $this->info("  Conversas criadas na fila: {$created}");
    }

    protected function processChat(WhatsappAccount $account, array $messageData): bool
    {
        $key = $messageData['key'] ?? [];
        $message = $messageData['message'] ?? [];

        $remoteJid = $key['remoteJid'];
        $messageId = $key['id'] ?? null;
        $pushName = $messageData['pushName'] ?? null;

        // Buscar ou criar chat
        $chat = Chat::firstOrCreate(
            ['account_id' => $account->id, 'chat_id' => $remoteJid],
            [
                'chat_name' => $pushName ?? $this->extractPhoneFromJid($remoteJid),
                'chat_type' => 'individual',
            ]
        );

        // Determinar conteúdo da mensagem
        $messageText = $this->extractMessageText($message);
        $messageType = $this->extractMessageType($message);

        // Criar mensagem se não existir
        if ($messageId) {
            Message::firstOrCreate(
                ['message_key' => $messageId],
                [
                    'chat_id' => $chat->id,
                    'from_jid' => $remoteJid,
                    'to_jid' => $account->owner_jid,
                    'message_text' => $messageText,
                    'message_type' => $messageType,
                    'is_from_me' => false,
                    'timestamp' => $messageData['messageTimestamp'] ?? time(),
                    'status' => 'delivered',
                    'message_raw' => $messageData,
                ]
            );
        }

        // Verificar se já existe conversa aberta para este chat
        $conversaExistente = Conversa::where('chat_id', $chat->id)
            ->whereIn('status', ['aguardando', 'em_atendimento'])
            ->first();

        if (!$conversaExistente) {
            // Criar nova conversa na fila
            Conversa::create([
                'cliente_numero' => $this->extractPhoneFromJid($remoteJid),
                'cliente_nome' => $pushName,
                'chat_id' => $chat->id,
                'account_id' => $account->id,
                'status' => 'aguardando',
                'iniciada_em' => now(),
                'ultima_msg_em' => now(),
                'cliente_aguardando_desde' => now(),
            ]);

            $phone = $this->extractPhoneFromJid($remoteJid);
            $this->line("    + Conversa criada: {$pushName} ({$phone})");
            return true;
        }

        return false;
    }

    protected function extractMessageText(array $message): ?string
    {
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }
        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }
        if (isset($message['imageMessage']['caption'])) {
            return $message['imageMessage']['caption'];
        }
        if (isset($message['videoMessage']['caption'])) {
            return $message['videoMessage']['caption'];
        }
        if (isset($message['documentMessage']['fileName'])) {
            return $message['documentMessage']['fileName'];
        }
        return null;
    }

    protected function extractMessageType(array $message): string
    {
        if (isset($message['conversation']) || isset($message['extendedTextMessage'])) {
            return 'text';
        }
        if (isset($message['imageMessage'])) {
            return 'image';
        }
        if (isset($message['videoMessage'])) {
            return 'video';
        }
        if (isset($message['audioMessage'])) {
            return 'audio';
        }
        if (isset($message['documentMessage'])) {
            return 'document';
        }
        if (isset($message['stickerMessage'])) {
            return 'sticker';
        }
        if (isset($message['locationMessage'])) {
            return 'location';
        }
        if (isset($message['contactMessage'])) {
            return 'contact';
        }
        return 'text';
    }

    protected function extractPhoneFromJid(string $jid): string
    {
        return explode('@', $jid)[0];
    }
}
