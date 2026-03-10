@extends('adminlte::page')

@section('title', 'Log')

@section('content_header')
    <h1><i class="fas fa-file-alt"></i> Detalhes do Log</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <span>
                @if($log->tipo === 'error')
                    <span class="badge badge-danger">{{ $log->tipo }}</span>
                @elseif($log->tipo === 'webhook')
                    <span class="badge badge-info">{{ $log->tipo }}</span>
                @else
                    <span class="badge badge-secondary">{{ $log->tipo }}</span>
                @endif
                {{ $log->created_at->format('d/m/Y H:i:s') }}
            </span>
            <a href="{{ route('admin.logs') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    <div class="card-body">
        <h5>Descricao:</h5>
        <p>{{ $log->descricao }}</p>

        @if($log->dados)
            <h5 class="mt-4">Dados:</h5>
            <pre class="bg-dark text-light p-3 rounded" style="max-height: 500px; overflow: auto;">{{ json_encode(json_decode($log->dados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
    </div>
</div>
@stop
