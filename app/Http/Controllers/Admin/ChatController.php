<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversa;
use App\Models\Message;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        // Conversas do atendente logado
        $conversas = Conversa::whereIn('account_id', $accountIds)
            ->where('atendente_id', $user->id)
            ->where('status', 'em_atendimento')
            ->with(['account'])
            ->orderBy('ultima_msg_em', 'desc')
            ->get();

        $conversaAtual = null;
        $mensagens = collect();

        if ($request->filled('conversa')) {
            $conversaAtual = Conversa::with(['account', 'chat.messages'])->find($request->conversa);

            if ($conversaAtual && $conversaAtual->chat) {
                $mensagens = $conversaAtual->chat->messages()
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }

        return view('admin.chat.index', compact('conversas', 'conversaAtual', 'mensagens'));
    }

    public function enviar(Request $request, Conversa $conversa)
    {
        $validated = $request->validate([
            'mensagem' => 'required|string|max:4096',
        ]);

        try {
            $service = app(EvolutionApiService::class);

            $result = $service->sendText(
                $conversa->account->session_name,
                $conversa->cliente_numero,
                $validated['mensagem']
            );

            // Salvar mensagem localmente
            if ($conversa->chat) {
                Message::create([
                    'chat_id' => $conversa->chat->id,
                    'message_id' => $result['key']['id'] ?? uniqid(),
                    'from_me' => true,
                    'sender' => $conversa->account->owner_jid,
                    'content' => $validated['mensagem'],
                    'type' => 'text',
                    'sent_by_user_id' => Auth::id(),
                ]);
            }

            $conversa->update(['ultima_msg_em' => now()]);

            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->back()->with('success', 'Mensagem enviada!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Erro ao enviar: ' . $e->getMessage());
        }
    }

    public function fila()
    {
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        $conversas = Conversa::whereIn('account_id', $accountIds)
            ->where('status', 'aguardando')
            ->with(['account'])
            ->orderBy('cliente_aguardando_desde', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.chat.fila', compact('conversas'));
    }
}
