@extends('adminlte::page')

@section('title', 'Monitor')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Monitor</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Pagina Inicial</a></li>
            <li class="breadcrumb-item active">Monitor</li>
        </ol>
    </div>
@stop

@section('css')
<style>
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

.info-box-custom .icon.bg-whatsapp { background: #25d366; }
.info-box-custom .icon.bg-warning { background: #ffc107; }
.info-box-custom .icon.bg-success { background: #28a745; }
.info-box-custom .icon.bg-info { background: #17a2b8; }

.info-box-custom .content .label {
    font-size: 0.85rem;
    color: #6c757d;
}

.info-box-custom .content .value {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
}

.info-box-custom .content .value small {
    font-size: 1rem;
    font-weight: 400;
    color: #6c757d;
}

.badge-status {
    font-size: 0.75rem;
    padding: 4px 10px;
}

.atividade-item {
    display: flex;
    padding: 10px 15px;
    border-bottom: 1px solid #f1f1f1;
    gap: 10px;
}

.atividade-item:last-child {
    border-bottom: none;
}

.atividade-item .direcao {
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}

.atividade-item .direcao i.fa-arrow-left {
    color: #17a2b8;
}

.atividade-item .direcao i.fa-arrow-right {
    color: #28a745;
}

.atividade-item .conteudo {
    flex: 1;
    min-width: 0;
}

.atividade-item .conteudo .nome {
    font-weight: 600;
    font-size: 0.9rem;
}

.atividade-item .conteudo .mensagem {
    font-size: 0.8rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.atividade-item .hora {
    font-size: 0.8rem;
    color: #6c757d;
    flex-shrink: 0;
}

.refresh-info {
    font-size: 0.8rem;
    color: #6c757d;
}

.refresh-info i {
    margin-right: 5px;
}

.atividade-scroll {
    max-height: 400px;
    overflow-y: auto;
}
</style>
@stop

@section('content')
{{-- Cards superiores --}}
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="info-box-custom">
            <div class="icon bg-whatsapp">
                <i class="fab fa-whatsapp"></i>
            </div>
            <div class="content">
                <div class="label">Instancias Online</div>
                <div class="value">{{ $stats['instancias_online'] }} <small>/ {{ $stats['instancias_total'] }}</small></div>
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
    <div class="col-lg-3 col-6">
        <div class="info-box-custom">
            <div class="icon bg-success">
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
            <div class="icon bg-info">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="content">
                <div class="label">Mensagens Hoje</div>
                <div class="value">{{ $stats['mensagens_hoje'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Coluna Esquerda --}}
    <div class="col-lg-7">
        {{-- Instancias WhatsApp --}}
        <div class="monitor-card">
            <div class="card-header">
                <h5><i class="fab fa-whatsapp"></i> Instancias WhatsApp</h5>
                <span class="badge badge-secondary">{{ $instancias->count() }}</span>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Sessao</th>
                            <th>Telefone</th>
                            <th>Empresa</th>
                            <th>Status</th>
                            <th>Ultima Conexao</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($instancias as $instancia)
                        <tr>
                            <td>{{ $instancia->session_name }}</td>
                            <td>{{ $instancia->owner_jid ? explode('@', $instancia->owner_jid)[0] : '-' }}</td>
                            <td>{{ $instancia->empresa?->nome ?? 'Scordon' }}</td>
                            <td>
                                @if($instancia->is_connected)
                                    <span class="badge badge-success badge-status">Online</span>
                                @else
                                    <span class="badge badge-danger badge-status">Offline</span>
                                @endif
                            </td>
                            <td>{{ $instancia->updated_at?->diffForHumans() ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Nenhuma instancia</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Conversas Ativas --}}
        <div class="monitor-card">
            <div class="card-header">
                <h5><i class="fas fa-comments"></i> Conversas Ativas</h5>
                <span class="badge badge-secondary">{{ $conversasAtivas->count() }}</span>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Atendente</th>
                            <th>Status</th>
                            <th>Espera Cliente</th>
                            <th>Inicio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conversasAtivas as $conversa)
                        @php
                            $ultimaMsg = $conversa->chat?->messages?->first();
                            $clienteRespondeu = $ultimaMsg && !$ultimaMsg->is_from_me;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $conversa->cliente_nome ?? 'Cliente' }}</strong>
                                <br><small class="text-muted">{{ $conversa->cliente_numero }}</small>
                            </td>
                            <td>
                                @if($conversa->atendente)
                                    {{ $conversa->atendente->name }}
                                @else
                                    <span class="text-muted">Sem atendente</span>
                                @endif
                            </td>
                            <td>
                                @if($conversa->status === 'em_atendimento')
                                    <span class="badge badge-success badge-status">Em atendimento</span>
                                @else
                                    <span class="badge badge-warning badge-status">Aguardando</span>
                                @endif
                            </td>
                            <td>
                                @if($clienteRespondeu)
                                    <span class="badge badge-info badge-status">Respondido</span>
                                @else
                                    <span class="badge badge-secondary badge-status">Aguardando</span>
                                @endif
                            </td>
                            <td>{{ $conversa->created_at?->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Nenhuma conversa ativa</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Coluna Direita --}}
    <div class="col-lg-5">
        {{-- Atendentes --}}
        <div class="monitor-card">
            <div class="card-header">
                <h5><i class="fas fa-users"></i> Atendentes</h5>
                <span class="badge badge-secondary">{{ $atendentes->count() }}</span>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Empresa</th>
                            <th>Status</th>
                            <th>Conversas</th>
                            <th>Ultimo Acesso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atendentes as $atendente)
                        <tr>
                            <td>{{ $atendente->name }}</td>
                            <td>{{ $atendente->empresa?->nome ?? 'Scordon' }}</td>
                            <td>
                                @if($atendente->status_atendimento === 'online')
                                    <span class="badge badge-success badge-status">Online</span>
                                @else
                                    <span class="badge badge-danger badge-status">Offline</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $atendente->conversas_ativas ?? 0 }}</strong>
                                <small class="text-muted">/ {{ $atendente->max_conversas_simultaneas ?? 8 }}</small>
                            </td>
                            <td>{{ $atendente->ultimo_acesso?->diffForHumans() ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Nenhum atendente</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Atividade Recente --}}
        <div class="monitor-card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Atividade Recente</h5>
                <span class="refresh-info"><i class="fas fa-sync-alt"></i> Atualiza a cada 30s</span>
            </div>
            <div class="card-body atividade-scroll">
                @forelse($atividadeRecente as $msg)
                <div class="atividade-item">
                    <div class="direcao">
                        @if($msg->is_from_me)
                            <i class="fas fa-arrow-right"></i>
                        @else
                            <i class="fas fa-arrow-left"></i>
                        @endif
                    </div>
                    <div class="conteudo">
                        <div class="nome">
                            {{ $msg->chat?->chat_name ?? $msg->from_jid ?? 'Desconhecido' }}
                        </div>
                        <div class="mensagem">
                            {{ Str::limit($msg->message_text ?? '[midia]', 50) }}
                        </div>
                    </div>
                    <div class="hora">
                        {{ $msg->created_at?->format('H:i') }}
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>Nenhuma atividade recente</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function() {
    // Auto-refresh a cada 30 segundos
    setTimeout(function() {
        location.reload();
    }, 30000);
});
</script>
@stop
