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
        $empresaId = $user->empresa_id;
        $accountIds = WhatsappAccount::where('empresa_id', $empresaId)->pluck('id');

        // Stats para os cards
        $stats = [
            'instancias_online' => WhatsappAccount::where('empresa_id', $empresaId)
                ->where('is_connected', true)
                ->count(),
            'instancias_total' => WhatsappAccount::where('empresa_id', $empresaId)->count(),
            'na_fila' => Conversa::whereIn('account_id', $accountIds)->where('status', 'aguardando')->count(),
            'em_atendimento' => Conversa::whereIn('account_id', $accountIds)->where('status', 'em_atendimento')->count(),
            'mensagens_hoje' => Message::whereHas('chat', fn($q) => $q->whereIn('account_id', $accountIds))
                ->whereDate('created_at', today())
                ->count(),
        ];

        // Instâncias WhatsApp
        $instancias = WhatsappAccount::where('empresa_id', $empresaId)
            ->with('empresa')
            ->orderBy('session_name')
            ->get();

        // Atendentes
        $atendentes = User::where('empresa_id', $empresaId)
            ->where('role', 'agent')
            ->withCount(['conversas as conversas_ativas' => function ($q) {
                $q->where('status', 'em_atendimento');
            }])
            ->orderBy('name')
            ->get();

        // Conversas ativas
        $conversasAtivas = Conversa::whereIn('account_id', $accountIds)
            ->whereIn('status', ['aguardando', 'em_atendimento'])
            ->with(['atendente', 'account', 'chat.messages' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Atividade recente (últimas mensagens)
        $atividadeRecente = Message::with(['chat.account'])
            ->whereHas('chat', fn($q) => $q->whereIn('account_id', $accountIds))
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return view('admin.monitor.index', compact(
            'stats',
            'instancias',
            'atendentes',
            'conversasAtivas',
            'atividadeRecente'
        ));
    }

    public function supervisao()
    {
        $user = Auth::user();
        $empresaId = $user->empresa_id;
        $accountIds = WhatsappAccount::where('empresa_id', $empresaId)->pluck('id');

        // Stats gerais
        $stats = [
            'online' => User::where('empresa_id', $empresaId)
                ->where('role', 'agent')
                ->where('status_atendimento', 'online')
                ->count(),
            'atendendo' => Conversa::whereIn('account_id', $accountIds)
                ->where('status', 'em_atendimento')
                ->distinct('atendente_id')
                ->count('atendente_id'),
            'na_fila' => Conversa::whereIn('account_id', $accountIds)
                ->where('status', 'aguardando')
                ->count(),
        ];

        // Atendentes
        $atendentes = User::where('empresa_id', $empresaId)
            ->where('role', 'agent')
            ->withCount(['conversas as conversas_ativas' => function ($q) {
                $q->where('status', 'em_atendimento');
            }])
            ->orderBy('name')
            ->get();

        // Conversas em atendimento com mensagens para preview
        $conversasEmAtendimento = Conversa::whereIn('account_id', $accountIds)
            ->where('status', 'em_atendimento')
            ->with(['atendente', 'account', 'chat.messages' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(10);
            }])
            ->orderBy('atendida_em', 'desc')
            ->get();

        return view('admin.monitor.supervisao', compact(
            'stats',
            'atendentes',
            'conversasEmAtendimento'
        ));
    }

    public function historico(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $empresaId = $user->empresa_id;
        $accountIds = WhatsappAccount::where('empresa_id', $empresaId)->pluck('id');

        // Stats gerais
        $stats = [
            'total' => Conversa::whereIn('account_id', $accountIds)->count(),
            'finalizadas' => Conversa::whereIn('account_id', $accountIds)->where('status', 'finalizada')->count(),
            'em_atendimento' => Conversa::whereIn('account_id', $accountIds)->where('status', 'em_atendimento')->count(),
            'na_fila' => Conversa::whereIn('account_id', $accountIds)->where('status', 'aguardando')->count(),
        ];

        // Atendentes com estatísticas
        $atendentes = User::where('empresa_id', $empresaId)
            ->where('role', 'agent')
            ->withCount([
                'conversas as em_atendimento' => function ($q) use ($accountIds) {
                    $q->whereIn('account_id', $accountIds)->where('status', 'em_atendimento');
                },
                'conversas as finalizadas' => function ($q) use ($accountIds) {
                    $q->whereIn('account_id', $accountIds)->where('status', 'finalizada');
                },
                'conversas as devolvidas' => function ($q) use ($accountIds) {
                    $q->whereIn('account_id', $accountIds)->whereNotNull('devolvida_por');
                },
            ])
            ->get()
            ->map(function ($atendente) use ($accountIds) {
                // Calcular tempo médio de atendimento
                $tempoMedio = Conversa::whereIn('account_id', $accountIds)
                    ->where('atendente_id', $atendente->id)
                    ->where('status', 'finalizada')
                    ->whereNotNull('atendida_em')
                    ->whereNotNull('finalizada_em')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, atendida_em, finalizada_em)) as tempo_medio')
                    ->value('tempo_medio');

                $atendente->tempo_medio = $tempoMedio ? round($tempoMedio) : 0;
                return $atendente;
            });

        // Query de conversas com filtros
        $query = Conversa::whereIn('account_id', $accountIds)
            ->with(['atendente', 'account']);

        // Filtro de busca
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cliente_nome', 'like', "%{$search}%")
                    ->orWhere('cliente_numero', 'like', "%{$search}%");
            });
        }

        // Filtro de atendente
        if ($request->filled('atendente_id')) {
            $query->where('atendente_id', $request->atendente_id);
        }

        // Filtro de status
        if ($request->filled('status') && is_array($request->status)) {
            $query->whereIn('status', $request->status);
        }

        // Filtro de período
        if ($request->filled('periodo')) {
            switch ($request->periodo) {
                case 'hoje':
                    $query->whereDate('created_at', today());
                    break;
                case 'semana':
                    $query->where('created_at', '>=', now()->startOfWeek());
                    break;
                case 'mes':
                    $query->where('created_at', '>=', now()->startOfMonth());
                    break;
                case '3meses':
                    $query->where('created_at', '>=', now()->subMonths(3));
                    break;
            }
        }

        $conversas = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.monitor.historico', compact('stats', 'atendentes', 'conversas'));
    }
}
