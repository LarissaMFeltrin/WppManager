<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappAccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = WhatsappAccount::with('empresa');

        if ($user->empresa_id) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $accounts = $query->orderBy('session_name')->paginate(15);

        return view('admin.whatsapp.index', compact('accounts'));
    }

    public function create()
    {
        $empresas = Empresa::where('status', true)->orderBy('nome')->get();

        return view('admin.whatsapp.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_name' => 'required|string|max:100|unique:whatsapp_accounts,session_name',
            'empresa_id' => 'required|exists:empresas,id',
            'is_active' => 'boolean',
        ]);

        WhatsappAccount::create([
            'session_name' => $validated['session_name'],
            'empresa_id' => $validated['empresa_id'],
            'user_id' => Auth::id(),
            'is_active' => $validated['is_active'] ?? true,
            'is_connected' => false,
        ]);

        return redirect()->route('admin.whatsapp.index')
            ->with('success', 'Instancia criada com sucesso!');
    }

    public function edit(WhatsappAccount $whatsapp)
    {
        $empresas = Empresa::where('status', true)->orderBy('nome')->get();

        return view('admin.whatsapp.edit', compact('whatsapp', 'empresas'));
    }

    public function update(Request $request, WhatsappAccount $whatsapp)
    {
        $validated = $request->validate([
            'session_name' => 'required|string|max:100|unique:whatsapp_accounts,session_name,' . $whatsapp->id,
            'empresa_id' => 'required|exists:empresas,id',
            'is_active' => 'boolean',
        ]);

        $whatsapp->update($validated);

        return redirect()->route('admin.whatsapp.index')
            ->with('success', 'Instancia atualizada com sucesso!');
    }

    public function destroy(WhatsappAccount $whatsapp)
    {
        if ($whatsapp->conversas()->count() > 0) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'Nao e possivel excluir instancia com conversas vinculadas!');
        }

        $whatsapp->delete();

        return redirect()->route('admin.whatsapp.index')
            ->with('success', 'Instancia excluida com sucesso!');
    }

    public function connect(WhatsappAccount $whatsapp)
    {
        try {
            $service = app(EvolutionApiService::class);
            $result = $service->connect($whatsapp->session_name);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function disconnect(WhatsappAccount $whatsapp)
    {
        try {
            $service = app(EvolutionApiService::class);
            $result = $service->logout($whatsapp->session_name);

            $whatsapp->update(['is_connected' => false]);

            return redirect()->route('admin.whatsapp.index')
                ->with('success', 'Instancia desconectada!');
        } catch (\Exception $e) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'Erro ao desconectar: ' . $e->getMessage());
        }
    }

    public function qrcode(WhatsappAccount $whatsapp)
    {
        try {
            $service = app(EvolutionApiService::class);
            $result = $service->getQrCode($whatsapp->session_name);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
