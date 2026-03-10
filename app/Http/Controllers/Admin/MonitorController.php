<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversa;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Auth;

class MonitorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

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
            'instancias_online' => WhatsappAccount::where('empresa_id', $user->empresa_id)
                ->where('is_connected', true)
                ->count(),
            'instancias_total' => WhatsappAccount::where('empresa_id', $user->empresa_id)->count(),
        ];

        $atendentes = Conversa::whereIn('account_id', $accountIds)
            ->where('status', 'em_atendimento')
            ->with('atendente')
            ->get()
            ->groupBy('atendente_id')
            ->map(fn($conversas) => [
                'nome' => $conversas->first()->atendente?->name ?? 'N/A',
                'conversas' => $conversas->count(),
            ])
            ->values();

        return view('admin.monitor.index', compact('stats', 'atendentes'));
    }

    public function supervisao()
    {
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        $atendentesStats = User::where('empresa_id', $user->empresa_id)
            ->where('role', 'agent')
            ->with(['conversas' => fn($q) => $q->whereIn('status', ['em_atendimento', 'finalizada'])])
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'nome' => $a->name,
                'status' => $a->status_atendimento,
                'em_atendimento' => $a->conversas->where('status', 'em_atendimento')->count(),
                'finalizadas_hoje' => $a->conversas->where('status', 'finalizada')
                    ->where('finalizada_em', '>=', today())->count(),
                'ultimo_acesso' => $a->ultimo_acesso?->diffForHumans(),
            ]);

        $conversasEmAtendimento = Conversa::whereIn('account_id', $accountIds)
            ->where('status', 'em_atendimento')
            ->with(['atendente', 'account'])
            ->orderBy('atendida_em', 'desc')
            ->get();

        $atendentes = User::where('empresa_id', $user->empresa_id)
            ->where('role', 'agent')
            ->orderBy('name')
            ->get();

        return view('admin.monitor.supervisao', compact('atendentesStats', 'conversasEmAtendimento', 'atendentes'));
    }

    public function historico()
    {
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        $conversas = Conversa::whereIn('account_id', $accountIds)
            ->where('status', 'finalizada')
            ->with(['atendente', 'account'])
            ->orderBy('finalizada_em', 'desc')
            ->paginate(20);

        return view('admin.monitor.historico', compact('conversas'));
    }
}
