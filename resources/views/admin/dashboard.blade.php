@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
@stop

@section('content')
<div class="row">
    {{-- Aguardando --}}
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['aguardando'] }}</h3>
                <p>Aguardando</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="{{ route('admin.fila') }}" class="small-box-footer">
                Ver fila <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- Em Atendimento --}}
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['em_atendimento'] }}</h3>
                <p>Em Atendimento</p>
            </div>
            <div class="icon">
                <i class="fas fa-comments"></i>
            </div>
            <a href="{{ route('admin.conversas.index') }}" class="small-box-footer">
                Ver conversas <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- Finalizadas Hoje --}}
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['finalizadas_hoje'] }}</h3>
                <p>Finalizadas Hoje</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('admin.historico') }}" class="small-box-footer">
                Ver historico <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- Mensagens Hoje --}}
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['mensagens_hoje'] }}</h3>
                <p>Mensagens Hoje</p>
            </div>
            <div class="icon">
                <i class="fas fa-envelope"></i>
            </div>
            <a href="#" class="small-box-footer">
                <i class="fas fa-info-circle"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    {{-- Instancias --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fab fa-whatsapp"></i> Instancias WhatsApp</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="mr-3">
                        <span class="display-4 {{ $stats['instancias_online'] > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $stats['instancias_online'] }}
                        </span>
                        <span class="text-muted">/ {{ $stats['instancias_total'] }}</span>
                    </div>
                    <div>
                        <p class="mb-0">instancias online</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-sm btn-outline-primary">
                    Gerenciar Instancias
                </a>
            </div>
        </div>
    </div>

    {{-- Atendentes --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users"></i> Atendentes</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="mr-3">
                        <span class="display-4 {{ $stats['atendentes_online'] > 0 ? 'text-success' : 'text-secondary' }}">
                            {{ $stats['atendentes_online'] }}
                        </span>
                        <span class="text-muted">/ {{ $stats['total_atendentes'] }}</span>
                    </div>
                    <div>
                        <p class="mb-0">atendentes online</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.supervisao') }}" class="btn btn-sm btn-outline-primary">
                    Ver Supervisao
                </a>
            </div>
        </div>
    </div>
</div>
@stop
