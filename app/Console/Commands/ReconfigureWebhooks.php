<?php

namespace App\Console\Commands;

use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Console\Command;

class ReconfigureWebhooks extends Command
{
    protected $signature = 'webhooks:reconfigure {--instance= : Nome da instância específica}';
    protected $description = 'Reconfigura webhooks das instâncias para incluir base64 de mídias';

    public function handle(EvolutionApiService $evolution)
    {
        $query = WhatsappAccount::query();

        if ($instance = $this->option('instance')) {
            $query->where('session_name', $instance);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('Nenhuma instância encontrada.');
            return 1;
        }

        $webhookUrl = config('app.url') . '/api/webhook';

        foreach ($accounts as $account) {
            $this->info("Reconfigurando webhook para: {$account->session_name}");

            try {
                $result = $evolution->setWebhook($account->session_name, $webhookUrl, [], true);

                if ($result['success']) {
                    $this->info("  ✓ Webhook configurado com sucesso (base64 habilitado)");
                } else {
                    $this->error("  ✗ Erro: " . json_encode($result));
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exceção: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Concluído! As mídias agora serão baixadas automaticamente.');

        return 0;
    }
}
