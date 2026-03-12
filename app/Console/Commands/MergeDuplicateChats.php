<?php

namespace App\Console\Commands;

use App\Models\WhatsappAccount;
use App\Services\ChatMergeService;
use Illuminate\Console\Command;

class MergeDuplicateChats extends Command
{
    protected $signature = 'chats:merge-duplicates {--account= : ID da conta específica}';
    protected $description = 'Mesclar chats duplicados do mesmo contato (mesmo nome)';

    public function handle(ChatMergeService $mergeService)
    {
        $accountId = $this->option('account');

        $accounts = $accountId
            ? WhatsappAccount::where('id', $accountId)->get()
            : WhatsappAccount::all();

        $totalMerged = 0;

        foreach ($accounts as $account) {
            $this->info("Processando conta: {$account->session_name}");

            $merged = $mergeService->findAndMergeDuplicates($account);

            if (count($merged) > 0) {
                foreach ($merged as $item) {
                    $this->line("  - Mesclado: {$item['primary']}");
                    $this->line("    JID principal: {$item['primary_jid']}");
                    $this->line("    JID mesclado: {$item['merged_jid']}");
                }
                $totalMerged += count($merged);
            } else {
                $this->line("  Nenhum duplicado encontrado.");
            }
        }

        $this->info("Total de chats mesclados: {$totalMerged}");

        return Command::SUCCESS;
    }
}
