<?php

namespace App\Filament\Pages;

use App\Models\Conversa;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class FilaEspera extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Fila de Espera';
    protected static ?string $navigationGroup = 'Atendimento';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.fila-espera';

    public array $conversasAguardando = [];

    public function mount(): void
    {
        $this->loadConversas();
    }

    public function loadConversas(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->conversasAguardando = Conversa::with(['chat', 'account'])
            ->where('status', 'aguardando')
            ->whereHas('account', fn($q) => $q->where('empresa_id', $user->empresa_id))
            ->orderBy('cliente_aguardando_desde', 'asc')
            ->get()
            ->toArray();
    }

    public function atenderConversa(int $conversaId): void
    {
        $conversa = Conversa::find($conversaId);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$conversa) {
            Notification::make()->title('Conversa não encontrada')->danger()->send();
            return;
        }

        $atendente = $user->atendente;
        if (!$atendente) {
            Notification::make()->title('Você não está cadastrado como atendente')->danger()->send();
            return;
        }

        $conversa->update([
            'atendente_id' => $atendente->id,
            'status' => 'em_atendimento',
            'atendida_em' => now(),
            'cliente_aguardando_desde' => null,
        ]);

        Notification::make()->title('Conversa assumida!')->success()->send();

        // Redirecionar para o console
        $this->redirect(ChatAtendimento::getUrl(['conversa' => $conversaId]));
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) return null;

        $count = Conversa::where('status', 'aguardando')
            ->whereHas('account', fn($q) => $q->where('empresa_id', $user->empresa_id))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
