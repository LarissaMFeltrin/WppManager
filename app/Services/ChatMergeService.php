<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Conversa;
use App\Models\ContactAlias;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatMergeService
{
    /**
     * Encontrar chat principal para um JID (verificando aliases)
     */
    public function findPrimaryChat(WhatsappAccount $account, string $jid): ?Chat
    {
        // Primeiro, verificar se existe alias para este JID
        $alias = ContactAlias::where('account_id', $account->id)
            ->where('alias_jid', $jid)
            ->first();

        if ($alias) {
            return $alias->primaryChat;
        }

        // Se não existe alias, buscar chat diretamente
        return Chat::where('account_id', $account->id)
            ->where('chat_id', $jid)
            ->first();
    }

    /**
     * Verificar se é um LID (número interno do WhatsApp)
     * LIDs geralmente são números grandes sem código de país
     */
    public function isLid(string $jid): bool
    {
        $number = explode('@', $jid)[0];

        // LIDs não começam com código de país (55 para Brasil)
        // e geralmente são muito longos (>15 dígitos)
        return strlen($number) > 15 || !preg_match('/^55\d{10,11}$/', $number);
    }

    /**
     * Mesclar dois chats em um só (move todas as mensagens e conversas)
     */
    public function mergeChats(Chat $primaryChat, Chat $secondaryChat): bool
    {
        if ($primaryChat->id === $secondaryChat->id) {
            return false;
        }

        try {
            DB::beginTransaction();

            // 1. Mover todas as mensagens do secundário para o primário
            $secondaryChat->messages()->update(['chat_id' => $primaryChat->id]);

            // 2. Mover/mesclar conversas
            $secondaryConversas = Conversa::where('chat_id', $secondaryChat->id)->get();

            foreach ($secondaryConversas as $conversa) {
                // Verificar se já existe conversa ativa no chat primário
                $existingConversa = Conversa::where('chat_id', $primaryChat->id)
                    ->whereIn('status', ['aguardando', 'em_atendimento'])
                    ->first();

                if ($existingConversa) {
                    // Se existe, finalizar a conversa secundária e manter a primária
                    $conversa->update([
                        'status' => 'finalizada',
                        'finalizada_em' => now(),
                    ]);
                } else {
                    // Se não existe, mover a conversa para o chat primário
                    $conversa->update(['chat_id' => $primaryChat->id]);
                }
            }

            // 3. Criar alias para o JID secundário apontando para o primário
            ContactAlias::updateOrCreate(
                [
                    'account_id' => $secondaryChat->account_id,
                    'alias_jid' => $secondaryChat->chat_id,
                ],
                [
                    'primary_chat_id' => $primaryChat->id,
                ]
            );

            // 4. Atualizar timestamp do chat primário se necessário
            if ($secondaryChat->last_message_timestamp > $primaryChat->last_message_timestamp) {
                $primaryChat->update([
                    'last_message_timestamp' => $secondaryChat->last_message_timestamp,
                ]);
            }

            // 5. Deletar o chat secundário (as mensagens já foram movidas)
            $secondaryChat->delete();

            DB::commit();

            Log::info('Chats mesclados com sucesso', [
                'primary_chat_id' => $primaryChat->id,
                'secondary_chat_id' => $secondaryChat->id,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao mesclar chats', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Listar possíveis duplicados para revisão manual
     * NÃO faz merge automático - apenas lista
     */
    public function findPossibleDuplicates(WhatsappAccount $account): array
    {
        $duplicates = [];

        // Buscar chats com mesmo nome
        $grouped = Chat::where('account_id', $account->id)
            ->where('chat_type', 'individual')
            ->whereNotNull('chat_name')
            ->where('chat_name', '!=', '')
            ->select('chat_name', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as chat_ids'))
            ->groupBy('chat_name')
            ->having('count', '>', 1)
            ->get();

        foreach ($grouped as $group) {
            $chatIds = explode(',', $group->chat_ids);
            $chats = Chat::whereIn('id', $chatIds)
                ->withCount('messages')
                ->orderBy('last_message_timestamp', 'desc')
                ->get();

            $duplicates[] = [
                'name' => $group->chat_name,
                'chats' => $chats->map(fn($c) => [
                    'id' => $c->id,
                    'jid' => $c->chat_id,
                    'messages_count' => $c->messages_count,
                ])->toArray(),
            ];
        }

        return $duplicates;
    }

    /**
     * DEPRECATED: Não usar - merge automático é arriscado
     * Usar findPossibleDuplicates + merge manual
     */
    public function findAndMergeDuplicates(WhatsappAccount $account): array
    {
        // Retornar vazio - não fazer merge automático
        return [];
    }

    /**
     * Vincular um JID como alias de um chat existente
     */
    public function linkAlias(Chat $primaryChat, string $aliasJid): bool
    {
        try {
            ContactAlias::updateOrCreate(
                [
                    'account_id' => $primaryChat->account_id,
                    'alias_jid' => $aliasJid,
                ],
                [
                    'primary_chat_id' => $primaryChat->id,
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao criar alias', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
