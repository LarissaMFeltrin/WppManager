@extends('adminlte::page')

@section('title', 'Fila de Espera')

@section('content_header')
    <h1><i class="fas fa-users-cog"></i> Fila de Espera</h1>
@stop

@section('content')
<div class="row">
    @forelse($conversas as $conversa)
        <div class="col-md-4">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $conversa->cliente_nome ?? 'Cliente' }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-warning">
                            {{ $conversa->cliente_aguardando_desde?->diffForHumans() ?? $conversa->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p><strong>Numero:</strong> {{ $conversa->cliente_numero }}</p>
                    <p><strong>Instancia:</strong> {{ $conversa->account?->session_name ?? '-' }}</p>
                    <p><strong>Aguardando desde:</strong> {{ $conversa->iniciada_em?->format('d/m/Y H:i') }}</p>
                </div>
                <div class="card-footer">
                    <form action="{{ route('admin.conversas.atender', $conversa) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-phone"></i> Atender
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h4>Nenhuma conversa na fila!</h4>
                    <p class="text-muted">Todas as conversas estao sendo atendidas.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>
@stop

@section('js')
<script>
// Auto-refresh a cada 10 segundos
setTimeout(function() { location.reload(); }, 10000);
</script>
@stop
