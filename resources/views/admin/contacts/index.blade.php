@extends('adminlte::page')

@section('title', 'Contatos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Contatos</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Pagina Inicial</a></li>
            <li class="breadcrumb-item active">Contatos</li>
        </ol>
    </div>
@stop

@section('css')
<style>
.contatos-header {
    background: #fff;
    padding: 15px 20px;
    border-radius: 5px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.contatos-header .titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.contatos-header .titulo i {
    color: #6c757d;
}

.filtros-bar {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filtros-bar .search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.filtros-bar .search-box input {
    padding-left: 40px;
    height: 42px;
}

.filtros-bar .search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
}

.filtros-bar .select-instancia {
    min-width: 220px;
}

.filtros-bar .select-instancia select {
    height: 42px;
}

.filtros-bar .btn {
    height: 42px;
    padding: 0 20px;
}

.contatos-table {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.contatos-table table {
    margin-bottom: 0;
}

.contatos-table th {
    background: #fff;
    border-top: none;
    font-weight: 600;
    color: #17a2b8;
    padding: 15px 20px;
    border-bottom: 2px solid #dee2e6;
}

.contatos-table td {
    padding: 12px 20px;
    vertical-align: middle;
}

.contatos-table tbody tr:hover {
    background: #f8f9fa;
}

.contatos-table .nome-link {
    color: #17a2b8;
    font-weight: 500;
    text-decoration: none;
}

.contatos-table .nome-link:hover {
    text-decoration: underline;
}

.contatos-table .badge-bloqueado {
    font-size: 0.8rem;
    padding: 5px 12px;
}

.contatos-table .btn-enviar {
    padding: 6px 15px;
    font-size: 0.85rem;
}

.contatos-vazio {
    background: #fff;
    padding: 60px 20px;
    border-radius: 5px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.contatos-vazio i {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 20px;
}

.pagination {
    margin: 0;
}
</style>
@stop

@section('content')
{{-- Header com Filtros --}}
<div class="contatos-header">
    <div class="titulo">
        <i class="fas fa-address-book"></i>
        Contatos
    </div>

    <form action="" method="GET" class="filtros-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-control"
                   value="{{ request('search') }}" placeholder="Buscar por nome ou numero...">
        </div>
        <div class="select-instancia">
            <select name="account_id" class="form-control">
                <option value="">-- Todas as Instancias --</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                        {{ $account->session_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-filter"></i> Filtrar
        </button>
        <a href="{{ route('admin.contatos.index') }}" class="btn btn-light">
            <i class="fas fa-times"></i> Limpar
        </a>
    </form>
</div>

{{-- Tabela de Contatos --}}
@if($contacts->count() > 0)
<div class="contatos-table">
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Numero</th>
                <th>Instancia</th>
                <th>Bloqueado</th>
                <th width="150"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($contacts as $contact)
            <tr>
                <td>
                    <a href="{{ route('admin.contatos.edit', $contact) }}" class="nome-link">
                        {{ $contact->name ?? 'Sem nome' }}
                    </a>
                </td>
                <td>{{ $contact->phone }}</td>
                <td>{{ $contact->account?->session_name ?? '-' }}</td>
                <td>
                    @if($contact->is_blocked)
                        <span class="badge badge-danger badge-bloqueado">Sim</span>
                    @else
                        <span class="badge badge-danger badge-bloqueado">Nao</span>
                    @endif
                </td>
                <td>
                    <button class="btn btn-success btn-enviar btn-enviar-msg"
                            data-phone="{{ $contact->phone }}"
                            data-name="{{ $contact->name }}"
                            data-account="{{ $contact->account_id }}">
                        <i class="fas fa-paper-plane"></i> Enviar Mensagem
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($contacts->hasPages())
    <div class="card-footer d-flex justify-content-center">
        {{ $contacts->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@else
<div class="contatos-vazio">
    <i class="fas fa-address-book"></i>
    <h4>Nenhum contato encontrado</h4>
    <p class="text-muted">Sincronize os contatos de uma instancia WhatsApp.</p>
    <button class="btn btn-success mt-3" data-toggle="modal" data-target="#sincronizarModal">
        <i class="fas fa-sync"></i> Sincronizar Contatos
    </button>
</div>
@endif

{{-- Modal Enviar Mensagem --}}
<div class="modal fade" id="enviarMsgModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar Mensagem</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Enviar para: <strong id="destinatarioNome"></strong></p>
                <p class="text-muted"><small id="destinatarioPhone"></small></p>

                <div class="form-group">
                    <label>Mensagem:</label>
                    <textarea id="mensagemTexto" class="form-control" rows="4" placeholder="Digite sua mensagem..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnEnviarMsg">
                    <i class="fas fa-paper-plane"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Sincronizar --}}
<div class="modal fade" id="sincronizarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.contatos.sincronizar') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Sincronizar Contatos</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Selecione a Instancia:</label>
                        <select name="account_id" class="form-control" required>
                            <option value="">Selecione...</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->session_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        A sincronizacao ira buscar todos os contatos do WhatsApp.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-sync"></i> Sincronizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function() {
    var currentPhone = null;
    var currentAccountId = null;

    // Abrir modal de enviar mensagem
    $('.btn-enviar-msg').on('click', function() {
        var phone = $(this).data('phone');
        var name = $(this).data('name');
        currentAccountId = $(this).data('account');
        currentPhone = phone;

        $('#destinatarioNome').text(name || 'Contato');
        $('#destinatarioPhone').text(phone);
        $('#mensagemTexto').val('');
        $('#enviarMsgModal').modal('show');
    });

    // Enviar mensagem
    $('#btnEnviarMsg').on('click', function() {
        var mensagem = $('#mensagemTexto').val().trim();
        if (!mensagem) {
            alert('Digite uma mensagem');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        $.ajax({
            url: '/admin/contatos/enviar-mensagem',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                phone: currentPhone,
                account_id: currentAccountId,
                mensagem: mensagem
            },
            success: function(response) {
                $('#enviarMsgModal').modal('hide');
                alert('Mensagem enviada com sucesso!');
            },
            error: function(xhr) {
                alert('Erro ao enviar: ' + (xhr.responseJSON?.error || 'Erro desconhecido'));
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar');
            }
        });
    });
});
</script>
@stop
