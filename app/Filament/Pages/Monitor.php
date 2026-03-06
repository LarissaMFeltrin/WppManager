<?php

namespace App\Filament\Pages;

use App\Models\Conversa;
use App\Models\Message;
use App\Models\WhatsappAccount;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Monitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Monitor';
    protected static ?string $navigationGroup = 'Monitoramento';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.monitor';

    public array $stats = [];
    public array $atendentes = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        $this->stats = [
            'aguardando' => Conversa::whereIn('account_id', $accountIds)->where('status', 'aguardando')->count(),
            'em_atendimento' => Conversa::whereIn('account_id', $accountIds)->where('status', 'em_atendimento')->count(),
            'finalizadas_hoje' => Conversa::whereIn('account_id', $accountIds)
                ->where('status', 'finalizada')
                ->whereDate('finalizada_em', today())
                ->count(),
            'mensagens_hoje' => Message::whereHas('chat', fn($q) => $q->whereIn('account_id', $accountIds))
                ->whereDate('created_at', today())
                ->count(),
            'instancias_online' => WhatsappAccount::where('empresa_id', $user->empresa_id)
                ->where('is_connected', true)
                ->count(),
            'instancias_total' => WhatsappAccount::where('empresa_id', $user->empresa_id)->count(),
        ];

        // Atendentes ativos
        $this->atendentes = Conversa::whereIn('account_id', $accountIds)
            ->where('status', 'em_atendimento')
            ->with('atendente')
            ->get()
            ->groupBy('atendente_id')
            ->map(fn($conversas) => [
                'nome' => $conversas->first()->atendente?->nome ?? 'N/A',
                'conversas' => $conversas->count(),
            ])
            ->values()
            ->toArray();
    }
}
