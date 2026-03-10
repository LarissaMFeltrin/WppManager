<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversa;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $empresaId = $user->empresa_id;

        $accountIds = WhatsappAccount::where('empresa_id', $empresaId)->pluck('id');

        $stats = [
            'aguardando' => Conversa::whereIn('account_id', $accountIds)->where('status', 'aguardando')->count(),
            'em_atendimento' => Conversa::whereIn('account_id', $accountIds)->where('status', 'em_atendimento')->count(),
            'finalizadas_hoje' => Conversa::whereIn('account_id', $accountIds)
                ->where('status', 'finalizada')
                ->whereDate('finalizada_em', today())
                ->count(),
            'mensagens_hoje' => Message::whereHas('chat', fn($q) => $q->whereIn('account_id', $accountIds))
                ->whereDate('created_at', today())
                ->count(),
            'instancias_online' => WhatsappAccount::where('empresa_id', $empresaId)
                ->where('is_connected', true)
                ->count(),
            'instancias_total' => WhatsappAccount::where('empresa_id', $empresaId)->count(),
            'atendentes_online' => User::where('empresa_id', $empresaId)
                ->where('role', 'agent')
                ->where('status_atendimento', 'online')
                ->count(),
            'total_atendentes' => User::where('empresa_id', $empresaId)
                ->where('role', 'agent')
                ->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
