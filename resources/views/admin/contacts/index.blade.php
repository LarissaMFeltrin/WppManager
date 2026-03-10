@extends('adminlte::page')

@section('title', 'Contatos')

@section('content_header')
    <h1><i class="fas fa-address-book"></i> Contatos</h1>
@stop

@section('content')
{{-- Filtros e Sincronizar --}}
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <form action="" method="GET" class="form-inline">
                    <input type="text" name="search" class="form-control mr-2" style="width: 300px;"
                           value="{{ request('search') }}" placeholder="Buscar por nome ou telefone">
                    <select name="account_id" class="form-control mr-2">
                        <option value="">Todas instancias</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->session_name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </form>
            </div>
            <div class="col-md-4 text-right">
                <button class="btn btn-success" data-toggle="modal" data-target="#sincronizarModal">
                    <i class="fas fa-sync"></i> Sincronizar Contatos
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Lista --}}
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Instancia</th>
                    <th width="100">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr>
                    <td>{{ $contact->name }}</td>
                    <td>{{ $contact->phone }}</td>
                    <td>{{ $contact->email ?? '-' }}</td>
                    <td>{{ $contact->account?->session_name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('admin.contatos.edit', $contact) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        Nenhum contato encontrado
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($contacts->hasPages())
    <div class="card-footer">
        {{ $contacts->appends(request()->query())->links() }}
    </div>
    @endif
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
                    <button type="submit" class="btn btn-success">Sincronizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop
