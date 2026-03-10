@extends('adminlte::page')

@section('title', 'Monitor')

@section('content_header')
    <h1><i class="fas fa-chart-bar"></i> Monitor</h1>
@stop

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['aguardando'] }}</h3>
                <p>Aguardando</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['em_atendimento'] }}</h3>
                <p>Em Atendimento</p>
            </div>
            <div class="icon"><i class="fas fa-comments"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['finalizadas_hoje'] }}</h3>
                <p>Finalizadas Hoje</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['mensagens_hoje'] }}</h3>
                <p>Mensagens Hoje</p>
            </div>
            <div class="icon"><i class="fas fa-envelope"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fab fa-whatsapp"></i> Instancias</h3>
            </div>
            <div class="card-body">
                <h2 class="{{ $stats['instancias_online'] > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $stats['instancias_online'] }} <small class="text-muted">/ {{ $stats['instancias_total'] }} online</small>
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users"></i> Atendentes Ativos</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($atendentes as $atendente)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user text-primary mr-2"></i> {{ $atendente['nome'] }}</span>
                            <span class="badge badge-info badge-pill">{{ $atendente['conversas'] }} conversa(s)</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted">
                            Nenhum atendente em atendimento
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
// Auto-refresh a cada 30 segundos
setTimeout(function() {
    location.reload();
}, 30000);
</script>
@stop
