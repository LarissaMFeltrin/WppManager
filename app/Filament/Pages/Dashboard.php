<?php

namespace App\Filament\Pages;

use App\Models\Chat;
use App\Models\Conversa;
use App\Models\Message;
use App\Models\WhatsappAccount;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Monitoramento';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $title = 'Dashboard';

    public array $stats = [];
    public array $ultimasMensagens = [];
    public array $instancias = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        $this->stats = [
            'total_chats' => Chat::whereIn('account_id', $accountIds)->count(),
            'mensagens_hoje' => Message::whereHas('chat', fn($q) => $q->whereIn('account_id', $accountIds))
                ->whereDate('created_at', today())
                ->count(),
            'instancias_online' => WhatsappAccount::where('empresa_id', $user->empresa_id)
                ->where('is_connected', true)
                ->count(),
            'conversas_ativas' => Conversa::whereIn('account_id', $accountIds)
                ->whereIn('status', ['aguardando', 'em_atendimento'])
                ->count(),
        ];

        $this->ultimasMensagens = Message::whereHas('chat', fn($q) => $q->whereIn('account_id', $accountIds))
            ->with('chat')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'chat_name' => $m->chat?->chat_name ?? '-',
                'message_text' => \Illuminate\Support\Str::limit($m->message_text ?? '[Midia]', 50),
                'message_type' => $m->message_type,
                'is_from_me' => $m->is_from_me,
                'created_at' => $m->created_at?->format('d/m/Y, H:i'),
            ])
            ->toArray();

        $this->instancias = WhatsappAccount::where('empresa_id', $user->empresa_id)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'session_name' => $a->session_name,
                'phone_number' => $a->phone_number,
                'is_connected' => $a->is_connected,
            ])
            ->toArray();
    }
}
