@extends('adminlte::page')

@section('title', 'Supervisao')

@section('content_header')
    <h1><i class="fas fa-eye"></i> Supervisao</h1>
@stop

@section('content')
{{-- Atendentes --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Atendentes</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Atendente</th>
                    <th>Status</th>
                    <th>Em Atendimento</th>
                    <th>Finalizadas Hoje</th>
                    <th>Ultimo Acesso</th>
                </tr>
            </thead>
            <tbody>
                @forelse($atendentesStats as $atendente)
                <tr>
                    <td><strong>{{ $atendente['nome'] }}</strong></td>
                    <td>
                        @if($atendente['status'] === 'online')
                            <span class="badge badge-success">Online</span>
                        @elseif($atendente['status'] === 'ocupado')
                            <span class="badge badge-warning">Ocupado</span>
                        @else
                            <span class="badge badge-secondary">Offline</span>
                        @endif
                    </td>
                    <td><span class="badge badge-info">{{ $atendente['em_atendimento'] }}</span></td>
                    <td>{{ $atendente['finalizadas_hoje'] }}</td>
                    <td>{{ $atendente['ultimo_acesso'] ?? 'Nunca' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        Nenhum atendente cadastrado
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Conversas em Atendimento --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Conversas em Atendimento ({{ $conversasEmAtendimento->count() }})</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Atendente</th>
                    <th>Instancia</th>
                    <th>Iniciada ha</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversasEmAtendimento as $conversa)
                <tr>
                    <td>
                        <strong>{{ $conversa->cliente_nome ?? 'Cliente' }}</strong>
                        <br><small class="text-muted">{{ $conversa->cliente_numero }}</small>
                    </td>
                    <td>{{ $conversa->atendente?->name ?? 'N/A' }}</td>
                    <td>{{ $conversa->account?->session_name ?? '-' }}</td>
                    <td>{{ $conversa->atendida_em?->diffForHumans() ?? '-' }}</td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-transferir"
                                data-id="{{ $conversa->id }}"
                                data-toggle="modal" data-target="#transferirModal">
                            <i class="fas fa-exchange-alt"></i> Transferir
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        Nenhuma conversa em atendimento
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Transferir --}}
<div class="modal fade" id="transferirModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTransferir" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Transferir Conversa</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Transferir para:</label>
                        <select name="atendente_id" class="form-control" required>
                            <option value="">Selecione...</option>
                            @foreach($atendentes as $atendente)
                                <option value="{{ $atendente->id }}">{{ $atendente->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Transferir</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function() {
    $('.btn-transferir').click(function() {
        var conversaId = $(this).data('id');
        $('#formTransferir').attr('action', '/admin/conversas/' + conversaId + '/transferir');
    });
});

// Auto-refresh
setTimeout(function() { location.reload(); }, 30000);
</script>
@stop
