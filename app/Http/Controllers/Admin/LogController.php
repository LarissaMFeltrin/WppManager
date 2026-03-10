<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogSistema;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = LogSistema::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('search')) {
            $query->where('descricao', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.logs.index', compact('logs'));
    }

    public function show(LogSistema $log)
    {
        return view('admin.logs.show', compact('log'));
    }

    public function limpar()
    {
        LogSistema::where('created_at', '<', now()->subDays(30))->delete();

        return redirect()->route('admin.logs')
            ->with('success', 'Logs antigos removidos!');
    }
}
