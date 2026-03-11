@extends('adminlte::page')

@section('title', 'Historico de Conversas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Historico de Conversas</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Pagina Inicial</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.monitor') }}">Monitor</a></li>
            <li class="breadcrumb-item active">Historico</li>
        </ol>
    </div>
@stop

@section('css')
<style>
.info-box-custom {
    display: flex;
    align-items: center;
    background: #fff;
    border-radius: 5px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    height: 100%;
}

.info-box-custom .icon {
    width: 60px;
    height: 60px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.info-box-custom .icon i {
    font-size: 1.8rem;
    color: #fff;
}

.info-box-custom .icon.bg-primary { background: #007bff; }
.info-box-custom .icon.bg-success { background: #28a745; }
.info-box-custom .icon.bg-info { background: #17a2b8; }
.info-box-custom .icon.bg-warning { background: #ffc107; }

.info-box-custom .content .label {
    font-size: 0.85rem;
    color: #6c757d;
}

.info-box-custom .content .value {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
}

.monitor-card {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 15px;
}

.monitor-card .card-header {
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.monitor-card .card-header h5 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.monitor-card .card-header h5 i {
    color: #6c757d;
}

.monitor-card .card-body {
    padding: 0;
}

.monitor-card table {
    margin-bottom: 0;
}

.monitor-card th {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #17a2b8;
    font-weight: 600;
    padding: 10px 15px;
    border-top: none;
}

.monitor-card td {
    padding: 10px 15px;
    vertical-align: middle;
    font-size: 0.9rem;
}

.badge-status {
    font-size: 0.75rem;
    padding: 4px 10px;
}

.filtros-card {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 15px;
    padding: 15px;
}

.filtros-card .form-group {
    margin-bottom: 0;
}

.filtros-card .form-control {
    font-size: 0.9rem;
}

.status-checkboxes {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.status-checkboxes .form-check {
    margin: 0;
}

.status-checkboxes .form-check-label {
    font-size: 0.9rem;
}

.btn-acao {
    padding: 4px 8px;
    font-size: 0.8rem;
}
</style>
@stop

@section('content')
{{-- Cards superiores --}}
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="info-box-custom">
            <div class="icon bg-primary">
                <i class="fas fa-comments"></i>
            </div>
            <div class="content">
                <div class="label">Total Conversas</div>
                <div class="value">{{ $stats['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box-custom">
            <div class="icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="content">
                <div class="label">Finalizadas</div>
                <div class="value">{{ $stats['finalizadas'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box-custom">
            <div class="icon bg-info">
                <i class="fas fa-headset"></i>
            </div>
            <div class="content">
                <div class="label">Em Atendimento</div>
                <div class="value">{{ $stats['em_atendimento'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box-custom">
            <div class="icon bg-warning">
                <i class="fas fa-users"></i>
            </div>
            <div class="content">
                <div class="label">Na Fila</div>
                <div class="value">{{ $stats['na_fila'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Desempenho por Atendente --}}
<div class="monitor-card">
    <div class="card-header">
        <h5><i class="fas fa-chart-bar"></i> Desempenho por Atendente</h5>
        <span class="badge badge-secondary">{{ $atendentes->count() }}</span>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Atendente</th>
                    <th>Status</th>
                    <th>Em Atendimento</th>
                    <th>Finalizadas</th>
                    <th>Devolvidas</th>
                    <th>Tempo Medio</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($atendentes as $atendente)
                <tr>
                    <td><strong>{{ $atendente->name }}</strong></td>
                    <td>
                        @if($atendente->status_atendimento === 'online')
                            <span class="badge badge-success badge-status">Online</span>
                        @else
                            <span class="badge badge-danger badge-status">Offline</span>
                        @endif
                    </td>
                    <td>{{ $atendente->em_atendimento }}</td>
                    <td>{{ $atendente->finalizadas }}</td>
                    <td>{{ $atendente->devolvidas }}</td>
                    <td>{{ $atendente->tempo_medio }}min</td>
                    <td>
                        <a href="{{ route('admin.historico', ['atendente_id' => $atendente->id]) }}"
                           class="btn btn-sm btn-outline-primary btn-acao">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-3">Nenhum atendente</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Filtros --}}
<div class="filtros-card">
    <form method="GET" action="{{ route('admin.historico') }}">
        <div class="row align-items-end">
            <div class="col-lg-3 col-md-6 mb-2">
                <div class="form-group">
                    <label>Buscar</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nome ou numero..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-2">
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
            <div class="col-lg-3 col-md-6 mb-2">
                <div class="form-group">
                    <label>Status</label>
                    <div class="status-checkboxes">
                        <div class="form-check">
                            <input type="checkbox" name="status[]" value="aguardando" class="form-check-input"
                                   id="statusAguardando" {{ in_array('aguardando', (array)request('status', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="statusAguardando">Aguardando</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="status[]" value="em_atendimento" class="form-check-input"
                                   id="statusAtendimento" {{ in_array('em_atendimento', (array)request('status', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="statusAtendimento">Em Atendimento</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="status[]" value="finalizada" class="form-check-input"
                                   id="statusFinalizada" {{ in_array('finalizada', (array)request('status', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="statusFinalizada">Finalizada</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-2">
                <div class="form-group">
                    <label>Periodo</label>
                    <select name="periodo" class="form-control">
                        <option value="">Todos</option>
                        <option value="hoje" {{ request('periodo') == 'hoje' ? 'selected' : '' }}>Hoje</option>
                        <option value="semana" {{ request('periodo') == 'semana' ? 'selected' : '' }}>Esta semana</option>
                        <option value="mes" {{ request('periodo') == 'mes' ? 'selected' : '' }}>Este mes</option>
                        <option value="3meses" {{ request('periodo') == '3meses' ? 'selected' : '' }}>Ultimos 3 meses</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-12 mb-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.historico') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Historico de Conversas --}}
<div class="monitor-card">
    <div class="card-header">
        <h5><i class="fas fa-history"></i> Historico de Conversas</h5>
        <span class="badge badge-secondary">{{ $conversas->total() }}</span>
    </div>
    <div class="card-body">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Numero</th>
                    <th>Atendente</th>
                    <th>Status</th>
                    <th>Inicio</th>
                    <th>Atendida</th>
                    <th>Finalizada</th>
                    <th>Duracao</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversas as $conversa)
                @php
                    $duracao = null;
                    if ($conversa->atendida_em && $conversa->finalizada_em) {
                        $duracao = $conversa->atendida_em->diff($conversa->finalizada_em)->format('%H:%I:%S');
                    } elseif ($conversa->atendida_em) {
                        $duracao = $conversa->atendida_em->diff(now())->format('%H:%I:%S');
                    }
                @endphp
                <tr>
                    <td>{{ $conversa->id }}</td>
                    <td><strong>{{ $conversa->cliente_nome ?? 'Cliente' }}</strong></td>
                    <td>{{ $conversa->cliente_numero }}</td>
                    <td>{{ $conversa->atendente?->name ?? '-' }}</td>
                    <td>
                        @if($conversa->status === 'finalizada')
                            <span class="badge badge-success badge-status">Finalizada</span>
                        @elseif($conversa->status === 'em_atendimento')
                            <span class="badge badge-info badge-status">Em atendimento</span>
                        @else
                            <span class="badge badge-warning badge-status">Aguardando</span>
                        @endif
                    </td>
                    <td>{{ $conversa->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $conversa->atendida_em?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $conversa->finalizada_em?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $duracao ?? '-' }}</td>
                    <td>
                        <a href="{{ route('admin.conversas.show', $conversa) }}"
                           class="btn btn-sm btn-outline-primary btn-acao" title="Ver conversa">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Nenhuma conversa encontrada
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($conversas->hasPages())
    <div class="card-footer">
        {{ $conversas->links() }}
    </div>
    @endif
</div>
@stop
