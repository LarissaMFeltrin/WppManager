@extends('adminlte::page')

@section('title', 'Logs')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1><i class="fas fa-file-alt"></i> Logs do Sistema</h1>
        <form action="{{ route('admin.logs.limpar') }}" method="POST"
              onsubmit="return confirm('Remover logs com mais de 30 dias?')">
            @csrf
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Limpar Antigos
            </button>
        </form>
    </div>
@stop

@section('content')
{{-- Filtros --}}
<div class="card">
    <div class="card-body">
        <form action="" method="GET" class="form-inline">
            <select name="tipo" class="form-control mr-2">
                <option value="">Todos os tipos</option>
                <option value="webhook" {{ request('tipo') == 'webhook' ? 'selected' : '' }}>Webhook</option>
                <option value="error" {{ request('tipo') == 'error' ? 'selected' : '' }}>Erro</option>
                <option value="info" {{ request('tipo') == 'info' ? 'selected' : '' }}>Info</option>
            </select>
            <input type="text" name="search" class="form-control mr-2" style="width: 300px;"
                   value="{{ request('search') }}" placeholder="Buscar na descricao">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </form>
    </div>
</div>

{{-- Lista --}}
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped" style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th width="150">Data/Hora</th>
                    <th width="100">Tipo</th>
                    <th>Descricao</th>
                    <th width="80">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>
                        @if($log->tipo === 'error')
                            <span class="badge badge-danger">{{ $log->tipo }}</span>
                        @elseif($log->tipo === 'webhook')
                            <span class="badge badge-info">{{ $log->tipo }}</span>
                        @else
                            <span class="badge badge-secondary">{{ $log->tipo }}</span>
                        @endif
                    </td>
                    <td>{{ Str::limit($log->descricao, 100) }}</td>
                    <td>
                        <a href="{{ route('admin.logs.show', $log) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        Nenhum log encontrado
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@stop
