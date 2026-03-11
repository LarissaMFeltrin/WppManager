<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Contact;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $accountIds = WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');

        $query = Contact::with('account')
            ->whereIn('account_id', $accountIds);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('jid', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $contacts = $query->orderBy('name')->paginate(30);

        $accounts = WhatsappAccount::whereIn('id', $accountIds)->orderBy('session_name')->get();

        return view('admin.contacts.index', compact('contacts', 'accounts'));
    }

    public function edit(Contact $contact)
    {
        return view('admin.contacts.edit', compact('contact'));
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $contact->update($validated);

        return redirect()->route('admin.contatos.index')
            ->with('success', 'Contato atualizado!');
    }

    public function sincronizarPage()
    {
        $user = Auth::user();
        $empresaId = $user->empresa_id;
        $accountIds = WhatsappAccount::where('empresa_id', $empresaId)->pluck('id');

        // Stats
        $totalContatos = Contact::whereIn('account_id', $accountIds)->count();
        $semNome = Contact::whereIn('account_id', $accountIds)
            ->where(function ($q) {
                $q->whereNull('name')
                    ->orWhere('name', '')
                    ->orWhere('name', 'Sem nome');
            })
            ->count();

        // Chats individuais sem contato associado
        $chatsSemContato = Chat::whereIn('account_id', $accountIds)
            ->where('chat_type', 'individual')
            ->whereNotExists(function ($q) {
                $q->select('id')
                    ->from('contacts')
                    ->whereColumn('contacts.jid', 'chats.chat_id')
                    ->whereColumn('contacts.account_id', 'chats.account_id');
            })
            ->count();

        $stats = [
            'total_contatos' => $totalContatos,
            'sem_nome' => $semNome,
            'chats_sem_contato' => $chatsSemContato,
        ];

        // Instâncias
        $instancias = WhatsappAccount::where('empresa_id', $empresaId)
            ->orderBy('session_name')
            ->get();

        return view('admin.contacts.sincronizar', compact('stats', 'instancias'));
    }

    public function sincronizar(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:whatsapp_accounts,id',
        ]);

        try {
            $account = WhatsappAccount::findOrFail($validated['account_id']);
            $service = app(EvolutionApiService::class);

            $response = $service->fetchContacts($account->session_name);

            if (!$response['success']) {
                throw new \Exception($response['error'] ?? 'Erro ao buscar contatos da API');
            }

            $contacts = $response['data'] ?? [];
            $count = 0;

            foreach ($contacts as $contact) {
                $jid = $contact['id'] ?? $contact['remoteJid'] ?? null;
                if (!$jid) {
                    continue;
                }

                // Extrair número do jid (remover @s.whatsapp.net)
                $phoneNumber = preg_replace('/@.*$/', '', $jid);

                Contact::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'jid' => $jid,
                    ],
                    [
                        'name' => $contact['pushName'] ?? $contact['name'] ?? 'Sem nome',
                        'phone_number' => $phoneNumber,
                        'profile_picture_url' => $contact['profilePictureUrl'] ?? null,
                    ]
                );
                $count++;
            }

            return redirect()->route('admin.contatos.sincronizar.page')
                ->with('success', "Sincronizados {$count} contatos!");
        } catch (\Exception $e) {
            return redirect()->route('admin.contatos.sincronizar.page')
                ->with('error', 'Erro ao sincronizar: ' . $e->getMessage());
        }
    }

    public function enviarMensagem(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'account_id' => 'required|exists:whatsapp_accounts,id',
            'mensagem' => 'required|string|max:4096',
        ]);

        try {
            $account = WhatsappAccount::findOrFail($validated['account_id']);
            $service = app(EvolutionApiService::class);

            // Extrair apenas o numero do phone (remover @s.whatsapp.net se houver)
            $phone = preg_replace('/@.*$/', '', $validated['phone']);

            $result = $service->sendText(
                $account->session_name,
                $phone,
                $validated['mensagem']
            );

            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
