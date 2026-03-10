@extends('adminlte::page')

@section('title', 'Instancias WhatsApp')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1><i class="fab fa-whatsapp"></i> Instancias WhatsApp</h1>
        <a href="{{ route('admin.whatsapp.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Instancia
        </a>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Nome da Sessao</th>
                    <th>Empresa</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Ultima Conexao</th>
                    <th width="200">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td>{{ $account->session_name }}</td>
                    <td>{{ $account->empresa?->nome ?? '-' }}</td>
                    <td>{{ $account->phone_number ?? '-' }}</td>
                    <td>
                        @if($account->is_connected)
                            <span class="badge badge-success"><i class="fas fa-check"></i> Conectado</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Desconectado</span>
                        @endif
                        @if(!$account->is_active)
                            <span class="badge badge-secondary">Inativo</span>
                        @endif
                    </td>
                    <td>{{ $account->last_connection?->diffForHumans() ?? 'Nunca' }}</td>
                    <td>
                        @if(!$account->is_connected)
                            <button class="btn btn-sm btn-success btn-qrcode" data-id="{{ $account->id }}">
                                <i class="fas fa-qrcode"></i> QR Code
                            </button>
                        @else
                            <form action="{{ route('admin.whatsapp.disconnect', $account) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="fas fa-sign-out-alt"></i> Desconectar
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.whatsapp.edit', $account) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.whatsapp.destroy', $account) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Tem certeza?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Nenhuma instancia cadastrada
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($accounts->hasPages())
    <div class="card-footer">
        {{ $accounts->links() }}
    </div>
    @endif
</div>

{{-- Modal QR Code --}}
<div class="modal fade" id="qrcodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Escanear QR Code</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="qrcode-container">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Carregando...</span>
                    </div>
                    <p class="mt-2">Gerando QR Code...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function() {
    $('.btn-qrcode').click(function() {
        var accountId = $(this).data('id');
        $('#qrcode-container').html('<div class="spinner-border text-primary"></div><p class="mt-2">Gerando QR Code...</p>');
        $('#qrcodeModal').modal('show');

        $.get('/admin/whatsapp/' + accountId + '/qrcode')
            .done(function(data) {
                if (data.qrcode) {
                    $('#qrcode-container').html('<img src="' + data.qrcode + '" class="img-fluid" style="max-width: 300px;">');
                } else if (data.base64) {
                    $('#qrcode-container').html('<img src="data:image/png;base64,' + data.base64 + '" class="img-fluid" style="max-width: 300px;">');
                } else {
                    $('#qrcode-container').html('<div class="alert alert-warning">QR Code nao disponivel. Tente novamente.</div>');
                }
            })
            .fail(function(xhr) {
                $('#qrcode-container').html('<div class="alert alert-danger">Erro ao gerar QR Code: ' + (xhr.responseJSON?.error || 'Erro desconhecido') + '</div>');
            });
    });
});
</script>
@stop
