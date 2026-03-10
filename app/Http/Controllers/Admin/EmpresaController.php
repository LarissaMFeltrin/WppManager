<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Empresa::withCount(['users', 'whatsappAccounts'])
            ->orderBy('nome')
            ->paginate(15);

        return view('admin.empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('admin.empresas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:100',
            'cnpj' => 'nullable|string|max:18',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'status' => 'boolean',
        ]);

        Empresa::create($validated);

        return redirect()->route('admin.empresas.index')
            ->with('success', 'Empresa criada com sucesso!');
    }

    public function edit(Empresa $empresa)
    {
        return view('admin.empresas.edit', compact('empresa'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:100',
            'cnpj' => 'nullable|string|max:18',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'status' => 'boolean',
        ]);

        $empresa->update($validated);

        return redirect()->route('admin.empresas.index')
            ->with('success', 'Empresa atualizada com sucesso!');
    }

    public function destroy(Empresa $empresa)
    {
        if ($empresa->users()->count() > 0) {
            return redirect()->route('admin.empresas.index')
                ->with('error', 'Nao e possivel excluir empresa com usuarios vinculados!');
        }

        $empresa->delete();

        return redirect()->route('admin.empresas.index')
            ->with('success', 'Empresa excluida com sucesso!');
    }
}
