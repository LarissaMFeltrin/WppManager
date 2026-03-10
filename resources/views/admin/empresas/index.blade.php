@extends('adminlte::page')

@section('title', 'Empresas')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1><i class="fas fa-building"></i> Empresas</h1>
        <a href="{{ route('admin.empresas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Empresa
        </a>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CNPJ</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Usuarios</th>
                    <th>Instancias</th>
                    <th>Status</th>
                    <th width="120">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($empresas as $empresa)
                <tr>
                    <td>{{ $empresa->nome }}</td>
                    <td>{{ $empresa->cnpj ?? '-' }}</td>
                    <td>{{ $empresa->telefone ?? '-' }}</td>
                    <td>{{ $empresa->email ?? '-' }}</td>
                    <td><span class="badge badge-info">{{ $empresa->users_count }}</span></td>
                    <td><span class="badge badge-success">{{ $empresa->whatsapp_accounts_count }}</span></td>
                    <td>
                        @if($empresa->status)
                            <span class="badge badge-success">Ativa</span>
                        @else
                            <span class="badge badge-danger">Inativa</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.empresas.edit', $empresa) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.empresas.destroy', $empresa) }}" method="POST" class="d-inline"
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
                    <td colspan="8" class="text-center text-muted py-4">
                        Nenhuma empresa cadastrada
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($empresas->hasPages())
    <div class="card-footer">
        {{ $empresas->links() }}
    </div>
    @endif
</div>
@stop
