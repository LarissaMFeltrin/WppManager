@extends('adminlte::page')

@section('title', 'Conversas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-comments"></i> Painel de Conversas</h1>
        @if($aguardando > 0)
            <span class="badge badge-warning badge-lg p-2">
                <i class="fas fa-clock"></i> {{ $aguardando }} aguardando
            </span>
        @endif
    </div>
@stop

@section('content')
{{-- Filtros --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Filtros</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="aguardando" {{ request('status') == 'aguardando' ? 'selected' : '' }}>Aguardando</option>
                        <option value="em_atendimento" {{ request('status') == 'em_atendimento' ? 'selected' : '' }}>Em Atendimento</option>
                        <option value="finalizada" {{ request('status') == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Atendente</label>
                    <select name="atendente_id" class="form-control">
                        <option value="">Todos</option>
                        @foreach($atendentes as $atendente)
                            <option value="{{ $atendente->id }}" {{ request('atendente_id') == $atendente->id ? 'selected' : '' }}>
                                {{ $atendente->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Buscar</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                           placeholder="Nome ou numero do cliente">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Lista --}}
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Instancia</th>
                    <th>Atendente</th>
                    <th>Status</th>
                    <th>Iniciada</th>
                    <th>Ultima Msg</th>
                    <th width="200">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversas as $conversa)
                <tr>
                    <td>
                        <strong>{{ $conversa->cliente_nome ?? 'Cliente' }}</strong>
                        <br><small class="text-muted">{{ $conversa->cliente_numero }}</small>
                    </td>
                    <td>{{ $conversa->account?->session_name ?? '-' }}</td>
                    <td>{{ $conversa->atendente?->name ?? '-' }}</td>
                    <td>
                        @if($conversa->status === 'aguardando')
                            <span class="badge badge-warning">Aguardando</span>
                        @elseif($conversa->status === 'em_atendimento')
                            <span class="badge badge-primary">Em Atendimento</span>
                        @else
                            <span class="badge badge-success">Finalizada</span>
                        @endif
                        @if($conversa->bloqueada)
                            <span class="badge badge-danger"><i class="fas fa-lock"></i></span>
                        @endif
                    </td>
                    <td>{{ $conversa->iniciada_em?->format('d/m/Y H:i') }}</td>
                    <td>{{ $conversa->ultima_msg_em?->diffForHumans() ?? '-' }}</td>
                    <td>
                        @if($conversa->status === 'aguardando')
                            <form action="{{ route('admin.conversas.atender', $conversa) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-phone"></i> Atender
                                </button>
                            </form>
                        @elseif($conversa->status === 'em_atendimento')
                            <a href="{{ route('admin.chat', ['conversa' => $conversa->id]) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-comment"></i> Chat
                            </a>
                            <form action="{{ route('admin.conversas.finalizar', $conversa) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="fas fa-check"></i> Finalizar
                                </button>
                            </form>
                        @else
                            <a href="{{ route('admin.conversas.show', $conversa) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Nenhuma conversa encontrada
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($conversas->hasPages())
    <div class="card-footer">
        {{ $conversas->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>
@stop
