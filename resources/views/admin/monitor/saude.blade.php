@extends('adminlte::page')

@section('title', 'Saude do Sistema')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-heartbeat text-danger"></i> Saude do Sistema</h1>
        <a href="{{ route('admin.saude') }}" class="btn btn-outline-secondary">
            <i class="fas fa-sync"></i> Atualizar
        </a>
    </div>
@stop

@section('css')
<style>
.health-card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}
.status-ok { background: #d4edda; color: #155724; }
.status-error { background: #f8d7da; color: #721c24; }
.status-warning { background: #fff3cd; color: #856404; }
.log-entry {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    font-family: monospace;
    font-size: 0.85rem;
}
.log-entry:last-child { border-bottom: none; }
.log-entry.ERROR { background: #fff5f5; }
.log-entry.WARNING { background: #fffbeb; }
.log-date { color: #6c757d; margin-right: 10px; }
.log-type { font-weight: bold; margin-right: 10px; }
.log-type.ERROR { color: #dc3545; }
.log-type.WARNING { color: #ffc107; }
.alert-card {
    border-left: 4px solid;
    margin-bottom: 10px;
}
.alert-card.alert-danger { border-left-color: #dc3545; }
.alert-card.alert-warning { border-left-color: #ffc107; }
.alert-card.alert-info { border-left-color: #17a2b8; }
</style>
@stop

@section('content')
{{-- Alertas --}}
@if(count($alertas) > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card health-card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-bell text-warning"></i> Alertas Ativos</h5>
            </div>
            <div class="card-body">
                @foreach($alertas as $alerta)
                <div class="alert alert-{{ $alerta['tipo'] }} alert-card d-flex align-items-center mb-2">
                    <i class="{{ $alerta['icone'] }} fa-2x mr-3"></i>
                    <div>
                        <strong>{{ $alerta['titulo'] }}</strong><br>
                        <small>{{ $alerta['mensagem'] }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@else
<div class="alert alert-success mb-4">
    <i class="fas fa-check-circle"></i> Sistema funcionando normalmente. Nenhum alerta ativo.
</div>
@endif

{{-- Status das Instancias --}}
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card health-card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fab fa-whatsapp text-success"></i> Status das Instancias</h5>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Instancia</th>
                            <th>Status DB</th>
                            <th>Status API</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statusInstancias as $inst)
                        <tr>
                            <td><strong>{{ $inst['nome'] }}</strong></td>
                            <td>
                                @if($inst['db_connected'])
                                    <span class="status-badge status-ok">Conectado</span>
                                @else
                                    <span class="status-badge status-error">Desconectado</span>
                                @endif
                            </td>
                            <td>
                                @if($inst['ok'])
                                    <span class="status-badge status-ok">{{ $inst['api_status'] }}</span>
                                @else
                                    <span class="status-badge status-error">{{ Str::limit($inst['api_status'], 30) }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">Nenhuma instancia</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card health-card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar text-primary"></i> Estatisticas</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <h3 class="mb-0 text-primary">{{ number_format($stats['total_chats']) }}</h3>
                        <small class="text-muted">Chats</small>
                    </div>
                    <div class="col-4">
                        <h3 class="mb-0 text-success">{{ number_format($stats['total_contatos']) }}</h3>
                        <small class="text-muted">Contatos</small>
                    </div>
                    <div class="col-4">
                        <h3 class="mb-0 text-info">{{ number_format($stats['total_mensagens']) }}</h3>
                        <small class="text-muted">Mensagens</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="mb-0">{{ $stats['mensagens_hoje'] }}</h4>
                        <small class="text-muted">Msgs Hoje</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0">{{ $stats['conversas_hoje'] }}</h4>
                        <small class="text-muted">Conversas Hoje</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0">{{ $stats['total_aliases'] }}</h4>
                        <small class="text-muted">Aliases LID</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Logs de Erros --}}
<div class="card health-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-danger"></i> Erros e Avisos Recentes</h5>
        <span class="badge badge-secondary">Ultimos 30</span>
    </div>
    <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
        @forelse($errosRecentes as $erro)
        <div class="log-entry {{ $erro['tipo'] }}">
            <span class="log-date">{{ $erro['data'] }}</span>
            <span class="log-type {{ $erro['tipo'] }}">{{ $erro['tipo'] }}</span>
            <span class="log-message">{{ $erro['mensagem'] }}</span>
        </div>
        @empty
        <div class="text-center text-muted py-4">
            <i class="fas fa-check-circle fa-2x mb-2"></i><br>
            Nenhum erro recente no log
        </div>
        @endforelse
    </div>
</div>
@stop
