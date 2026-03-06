<?php

namespace App\Filament\Pages;

use App\Models\Contact;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SincronizarContatos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Sincronizar Contatos';
    protected static ?string $navigationGroup = 'Monitoramento';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.sincronizar-contatos';

    public array $instancias = [];
    public bool $sincronizando = false;

    public function mount(): void
    {
        $this->loadInstancias();
    }

    public function loadInstancias(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->instancias = WhatsappAccount::where('empresa_id', $user->empresa_id)
            ->withCount('contacts')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'session_name' => $a->session_name,
                'phone_number' => $a->phone_number,
                'is_connected' => $a->is_connected,
                'contacts_count' => $a->contacts_count,
                'last_full_sync' => $a->last_full_sync?->diffForHumans(),
            ])
            ->toArray();
    }

    public function sincronizar(int $accountId): void
    {
        $this->sincronizando = true;

        $account = WhatsappAccount::find($accountId);
        if (!$account) {
            Notification::make()->title('Instância não encontrada')->danger()->send();
            $this->sincronizando = false;
            return;
        }

        if (!$account->is_connected) {
            Notification::make()->title('Instância desconectada')->danger()->send();
            $this->sincronizando = false;
            return;
        }

        try {
            $api = app(EvolutionApiService::class);
            $result = $api->fetchContacts($account->session_name);

            if ($result['success'] && isset($result['data'])) {
                $count = 0;
                foreach ($result['data'] as $contact) {
                    Contact::updateOrCreate(
                        [
                            'account_id' => $accountId,
                            'remote_jid' => $contact['id'] ?? $contact['remoteJid'] ?? null,
                        ],
                        [
                            'push_name' => $contact['pushName'] ?? $contact['name'] ?? null,
                            'profile_picture_url' => $contact['profilePictureUrl'] ?? null,
                        ]
                    );
                    $count++;
                }

                $account->update(['last_full_sync' => now()]);

                Notification::make()
                    ->title('Sincronização concluída')
                    ->body("$count contatos sincronizados")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Erro na sincronização')
                    ->body($result['error'] ?? 'Erro desconhecido')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->sincronizando = false;
        $this->loadInstancias();
    }
}
