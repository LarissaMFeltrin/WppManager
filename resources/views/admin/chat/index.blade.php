@extends('adminlte::page')

@section('title', 'Chat')

@section('content_header')
    <h1><i class="fas fa-comment-dots"></i> Chat de Atendimento</h1>
@stop

@section('css')
<style>
.chat-container { display: flex; height: calc(100vh - 200px); }
.chat-sidebar { width: 300px; border-right: 1px solid #ddd; overflow-y: auto; }
.chat-main { flex: 1; display: flex; flex-direction: column; }
.chat-messages { flex: 1; overflow-y: auto; padding: 15px; background: #f4f6f9; }
.chat-input { padding: 15px; border-top: 1px solid #ddd; background: #fff; }
.message { margin-bottom: 10px; max-width: 70%; }
.message-received { margin-right: auto; }
.message-sent { margin-left: auto; }
.message-content { padding: 10px 15px; border-radius: 10px; }
.message-received .message-content { background: #fff; }
.message-sent .message-content { background: #dcf8c6; }
.contact-item { padding: 10px 15px; border-bottom: 1px solid #eee; cursor: pointer; }
.contact-item:hover, .contact-item.active { background: #e9ecef; }
.contact-item .badge { float: right; }
</style>
@stop

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="chat-container">
            {{-- Sidebar --}}
            <div class="chat-sidebar">
                <div class="p-3 bg-light border-bottom">
                    <strong>Minhas Conversas</strong>
                </div>
                @forelse($conversas as $c)
                    <a href="{{ route('admin.chat', ['conversa' => $c->id]) }}"
                       class="contact-item d-block text-dark text-decoration-none {{ $conversaAtual && $conversaAtual->id == $c->id ? 'active' : '' }}">
                        <strong>{{ $c->cliente_nome ?? 'Cliente' }}</strong>
                        <br><small class="text-muted">{{ $c->cliente_numero }}</small>
                        <br><small class="text-muted">{{ $c->account?->session_name }}</small>
                    </a>
                @empty
                    <div class="p-3 text-center text-muted">
                        Nenhuma conversa ativa
                    </div>
                @endforelse
            </div>

            {{-- Chat Main --}}
            <div class="chat-main">
                @if($conversaAtual)
                    {{-- Header --}}
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $conversaAtual->cliente_nome ?? 'Cliente' }}</strong>
                            <br><small class="text-muted">{{ $conversaAtual->cliente_numero }}</small>
                        </div>
                        <div>
                            <form action="{{ route('admin.conversas.finalizar', $conversaAtual) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fas fa-check"></i> Finalizar
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Messages --}}
                    <div class="chat-messages" id="chatMessages">
                        @foreach($mensagens as $msg)
                            <div class="message {{ $msg->from_me ? 'message-sent' : 'message-received' }}">
                                <div class="message-content">
                                    {{ $msg->content }}
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ $msg->created_at->format('H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Input --}}
                    <div class="chat-input">
                        <form action="{{ route('admin.chat.enviar', $conversaAtual) }}" method="POST">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="mensagem" class="form-control"
                                       placeholder="Digite sua mensagem..." required autofocus>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center">
                            <i class="fas fa-comments fa-4x mb-3"></i>
                            <p>Selecione uma conversa para iniciar</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function() {
    // Scroll to bottom
    var chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Auto-refresh messages every 5 seconds
    @if($conversaAtual)
    setInterval(function() {
        // Could implement AJAX refresh here
    }, 5000);
    @endif
});
</script>
@stop
