@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1><i class="fas fa-users"></i> Usuarios</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Usuario
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
                    <th>E-mail</th>
                    <th>Empresa</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Conversas</th>
                    <th width="120">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->empresa?->nome ?? '-' }}</td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge badge-danger">Administrador</span>
                        @elseif($user->role === 'supervisor')
                            <span class="badge badge-warning">Supervisor</span>
                        @else
                            <span class="badge badge-primary">Agente</span>
                        @endif
                    </td>
                    <td>
                        @if($user->role === 'agent')
                            @if($user->status_atendimento === 'online')
                                <span class="badge badge-success">Online</span>
                            @elseif($user->status_atendimento === 'ocupado')
                                <span class="badge badge-warning">Ocupado</span>
                            @else
                                <span class="badge badge-secondary">Offline</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($user->role === 'agent')
                            {{ $user->conversas_ativas }}/{{ $user->max_conversas }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
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
                    <td colspan="7" class="text-center text-muted py-4">
                        Nenhum usuario cadastrado
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>
@stop
