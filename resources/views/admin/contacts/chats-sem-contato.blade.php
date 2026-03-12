@extends('adminlte::page')

@section('title', 'Chats sem Contato')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Chats sem Contato</h1>
        <a href="{{ route('admin.contatos.sincronizar.page') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                Chats individuais sem registro de contato
            </h3>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Estes chats existem no sistema mas nao possuem um contato associado.
                Voce pode criar contatos para eles executando a sincronizacao ou manualmente.
            </p>

            @if($chats->isEmpty())
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Todos os chats possuem contatos associados.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Chat ID (JID)</th>
                                <th>Nome do Chat</th>
                                <th>Instancia</th>
                                <th>Mensagens</th>
                                <th>Ultima atividade</th>
                                <th>Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chats as $chat)
                                <tr>
                                    <td><code>{{ $chat->chat_id }}</code></td>
                                    <td>{{ $chat->chat_name ?: '-' }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $accounts[$chat->account_id] ?? 'Desconhecida' }}</span>
                                    </td>
                                    <td>{{ $chat->messages_count }}</td>
                                    <td>
                                        @if($chat->last_message_timestamp)
                                            {{ \Carbon\Carbon::createFromTimestamp($chat->last_message_timestamp)->diffForHumans() }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.contatos.criar-do-chat') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="chat_id" value="{{ $chat->id }}">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-user-plus"></i> Criar Contato
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $chats->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@stop
