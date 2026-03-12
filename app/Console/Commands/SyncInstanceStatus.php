<?php

namespace App\Console\Commands;

use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Console\Command;

class SyncInstanceStatus extends Command
{
    protected $signature = 'instances:sync-status';
    protected $description = 'Sincroniza o status de conexao das instancias com a Evolution API';

    public function handle(EvolutionApiService $evolution)
    {
        $accounts = WhatsappAccount::all();

        foreach ($accounts as $account) {
            try {
                $result = $evolution->getConnectionState($account->session_name);
                $state = $result['data']['instance']['state'] ?? 'unknown';
                $isConnected = $state === 'open';

                if ($account->is_connected !== $isConnected) {
                    $account->update([
                        'is_connected' => $isConnected,
                        'last_connection' => $isConnected ? now() : $account->last_connection,
                    ]);
                    $this->info("{$account->session_name}: " . ($isConnected ? 'CONECTADO' : 'DESCONECTADO'));
                } else {
                    $this->line("{$account->session_name}: sem mudanca ({$state})");
                }
            } catch (\Exception $e) {
                $this->error("{$account->session_name}: Erro - " . $e->getMessage());
                $account->update(['is_connected' => false]);
            }
        }

        $this->info('Sincronizacao concluida!');
        return 0;
    }
}
