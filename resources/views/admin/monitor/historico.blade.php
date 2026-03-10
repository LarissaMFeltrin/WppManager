@extends('adminlte::page')

@section('title', 'Historico')

@section('content_header')
    <h1><i class="fas fa-history"></i> Historico de Conversas</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Numero</th>
                    <th>Atendente</th>
                    <th>Instancia</th>
                    <th>Inicio</th>
                    <th>Fim</th>
                    <th>Duracao</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversas as $conversa)
                <tr>
                    <td>{{ $conversa->cliente_nome ?? '-' }}</td>
                    <td>{{ $conversa->cliente_numero }}</td>
                    <td>{{ $conversa->atendente?->name ?? '-' }}</td>
                    <td>{{ $conversa->account?->session_name ?? '-' }}</td>
                    <td>{{ $conversa->iniciada_em?->format('d/m/Y H:i') }}</td>
                    <td>{{ $conversa->finalizada_em?->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($conversa->iniciada_em && $conversa->finalizada_em)
                            {{ $conversa->iniciada_em->diff($conversa->finalizada_em)->format('%H:%I:%S') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Nenhuma conversa finalizada
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
