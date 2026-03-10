<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
                    ->orWhere('phone', 'like', "%{$search}%");
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

    public function sincronizar(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:whatsapp_accounts,id',
        ]);

        try {
            $account = WhatsappAccount::findOrFail($validated['account_id']);
            $service = app(EvolutionApiService::class);

            $contacts = $service->getContacts($account->session_name);

            $count = 0;
            foreach ($contacts as $contact) {
                Contact::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'phone' => $contact['id'] ?? $contact['remoteJid'] ?? null,
                    ],
                    [
                        'name' => $contact['pushName'] ?? $contact['name'] ?? 'Sem nome',
                        'profile_pic' => $contact['profilePictureUrl'] ?? null,
                    ]
                );
                $count++;
            }

            return redirect()->back()
                ->with('success', "Sincronizados {$count} contatos!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao sincronizar: ' . $e->getMessage());
        }
    }
}
