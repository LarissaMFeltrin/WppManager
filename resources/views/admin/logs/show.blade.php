@extends('adminlte::page')

@section('title', 'Detalhes do Log')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Detalhes do Log #{{ $log->id }}</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Pagina Inicial</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.logs') }}">Logs</a></li>
            <li class="breadcrumb-item active">Detalhes</li>
        </ol>
    </div>
@stop

@section('css')
<style>
.log-card {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.log-card .card-header {
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 15px;
}

.badge-tipo {
    font-size: 0.8rem;
    padding: 5px 12px;
    border-radius: 3px;
}

.badge-tipo.erro { background: #dc3545; color: #fff; }
.badge-tipo.info { background: #17a2b8; color: #fff; }
.badge-tipo.atendimento { background: #20c997; color: #fff; }
.badge-tipo.webhook { background: #6f42c1; color: #fff; }

.badge-nivel {
    font-size: 0.8rem;
    padding: 5px 12px;
    border-radius: 3px;
}

.badge-nivel.debug { background: #6c757d; color: #fff; }
.badge-nivel.info { background: #17a2b8; color: #fff; }
.badge-nivel.warning { background: #ffc107; color: #212529; }
.badge-nivel.error { background: #dc3545; color: #fff; }

.log-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.log-info-item label {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 5px;
    display: block;
}

.log-info-item .value {
    font-size: 0.95rem;
    font-weight: 500;
}

.log-mensagem {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 0.95rem;
    line-height: 1.6;
}

.log-dados {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 15px;
    border-radius: 5px;
    max-height: 400px;
    overflow: auto;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.85rem;
    line-height: 1.5;
}
</style>
@stop

@section('content')
<div class="log-card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span class="badge-tipo {{ $log->tipo }}">{{ $log->tipo }}</span>
                <span class="badge-nivel {{ $log->nivel }}">{{ $log->nivel }}</span>
            </div>
            <a href="{{ route('admin.logs') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="log-info-grid">
            <div class="log-info-item">
                <label>ID</label>
                <div class="value">{{ $log->id }}</div>
            </div>
            <div class="log-info-item">
                <label>Tipo</label>
                <div class="value">{{ $log->tipo }}</div>
            </div>
            <div class="log-info-item">
                <label>Nivel</label>
                <div class="value">{{ $log->nivel }}</div>
            </div>
            <div class="log-info-item">
                <label>IP de Origem</label>
                <div class="value">{{ $log->ip_origem ?? '-' }}</div>
            </div>
            <div class="log-info-item">
                <label>Data/Hora</label>
                <div class="value">{{ $log->criada_em?->format('d/m/Y H:i:s') ?? '-' }}</div>
            </div>
            <div class="log-info-item">
                <label>User Agent</label>
                <div class="value" style="font-size: 0.8rem; word-break: break-all;">{{ $log->user_agent ?? '-' }}</div>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-2">Mensagem</h6>
        <div class="log-mensagem">
            {{ $log->mensagem }}
        </div>

        @if($log->dados)
            <h6 class="text-uppercase text-muted mb-2">Dados Adicionais</h6>
            <pre class="log-dados">{{ json_encode($log->dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
    </div>
</div>
@stop
