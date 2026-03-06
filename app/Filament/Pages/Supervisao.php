<?php

namespace App\Filament\Pages;

use App\Models\Atendente;
use App\Models\Conversa;
use App\Models\WhatsappAccount;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class Supervisao extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationLabel = 'Supervisao';
    protected static ?string $navigationGroup = 'Monitoramento';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.supervisao';

    public array $atendentesStats = [];
    public array $conversasEmAtendimento = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        // Stats por atendente
        $atendentes = Atendente::where('empresa_id', $user->empresa_id)
            ->with(['conversas' => fn($q) => $q->whereIn('status', ['em_atendimento', 'finalizada'])])
            ->get();

        $this->atendentesStats = $atendentes->map(fn($a) => [
            'id' => $a->id,
            'nome' => $a->nome,
            'status' => $a->status,
            'em_atendimento' => $a->conversas->where('status', 'em_atendimento')->count(),
            'finalizadas_hoje' => $a->conversas->where('status', 'finalizada')
                ->where('finalizada_em', '>=', today())->count(),
            'ultimo_acesso' => $a->ultimo_acesso?->diffForHumans(),
        ])->toArray();

        // Conversas em atendimento
        $this->conversasEmAtendimento = Conversa::whereIn('account_id', $accountIds)
            ->where('status', 'em_atendimento')
            ->with(['atendente', 'account', 'chat'])
            ->orderBy('atendida_em', 'desc')
            ->get()
            ->toArray();
    }

    public function transferirConversa(int $conversaId, int $atendenteId): void
    {
        $conversa = Conversa::find($conversaId);
        if ($conversa) {
            $conversa->update([
                'atendente_id' => $atendenteId,
            ]);
            Notification::make()->title('Conversa transferida')->success()->send();
            $this->loadData();
        }
    }
}
