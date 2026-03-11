@extends('adminlte::page')

@section('title', 'Painel de Conversas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Dashboard de Atendimento</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Pagina Inicial</a></li>
            <li class="breadcrumb-item active">Dashboard de Atendimento</li>
        </ol>
    </div>
@stop

@section('css')
<style>
:root {
    --chat-bg: #e5ddd5;
    --msg-sent-bg: #dcf8c6;
    --msg-received-bg: #ffffff;
    --header-bg: #f0f2f5;
}

.painel-header {
    background: #075e54;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.painel-header .info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.painel-header .info i {
    font-size: 1.5rem;
}

.painel-header .actions {
    display: flex;
    gap: 10px;
}

.chats-container {
    display: flex;
    gap: 15px;
    overflow-x: auto;
    padding-bottom: 15px;
    min-height: calc(100vh - 250px);
}

.chat-column {
    min-width: 380px;
    max-width: 420px;
    flex: 1;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 250px);
}

.chat-column-header {
    background: var(--header-bg);
    padding: 10px 15px;
    border-radius: 8px 8px 0 0;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #ddd;
}

.chat-column-header .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dfe5e7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #fff;
    font-size: 0.9rem;
}

.chat-column-header .avatar.color-1 { background: #25d366; }
.chat-column-header .avatar.color-2 { background: #128c7e; }
.chat-column-header .avatar.color-3 { background: #075e54; }
.chat-column-header .avatar.color-4 { background: #34b7f1; }
.chat-column-header .avatar.color-5 { background: #00a884; }

.chat-column-header .info {
    flex: 1;
}

.chat-column-header .info .name {
    font-weight: 600;
    font-size: 0.95rem;
}

.chat-column-header .info .number {
    font-size: 0.8rem;
    color: #667781;
}

.chat-column-header .info .badge-group {
    font-size: 0.7rem;
    background: #25d366;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
}

.chat-column-header .actions {
    display: flex;
    gap: 5px;
}

.chat-column-header .actions .btn {
    padding: 5px 8px;
    font-size: 0.85rem;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background: var(--chat-bg);
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c8c8c8' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.message {
    margin-bottom: 8px;
    display: flex;
    position: relative;
}

.message.sent {
    justify-content: flex-end;
}

.message.received {
    justify-content: flex-start;
}

.message-bubble {
    max-width: 85%;
    padding: 6px 10px;
    border-radius: 8px;
    position: relative;
    box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
    cursor: pointer;
}

.message.sent .message-bubble {
    background: var(--msg-sent-bg);
    border-radius: 8px 0 8px 8px;
}

.message.received .message-bubble {
    background: var(--msg-received-bg);
    border-radius: 0 8px 8px 8px;
}

.message-sender {
    font-size: 0.75rem;
    font-weight: 600;
    color: #075e54;
    margin-bottom: 2px;
}

.message-quoted {
    background: rgba(0,0,0,0.05);
    border-left: 3px solid #25d366;
    padding: 5px 8px;
    margin-bottom: 5px;
    border-radius: 3px;
    font-size: 0.8rem;
    color: #667781;
}

.message-text {
    word-wrap: break-word;
    font-size: 0.9rem;
    line-height: 1.4;
}

.message-time {
    font-size: 0.7rem;
    color: #667781;
    text-align: right;
    margin-top: 3px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 3px;
}

.message.sent .message-time .check {
    color: #53bdeb;
}

.message-deleted {
    font-style: italic;
    color: #8696a0;
}

.message-edited {
    font-size: 0.7rem;
    color: #8696a0;
    margin-right: 5px;
}

.message-reactions {
    display: flex;
    gap: 3px;
    margin-top: 3px;
}

.message-reactions .reaction {
    background: rgba(0,0,0,0.05);
    padding: 2px 5px;
    border-radius: 10px;
    font-size: 0.8rem;
}

/* Date separator */
.message-date-separator {
    display: flex;
    justify-content: center;
    margin: 15px 0;
}

.message-date-separator span {
    background: #e1f3fb;
    color: #54656f;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
}

/* Media */
.message-media-img {
    max-width: 250px;
    max-height: 300px;
    border-radius: 5px;
    cursor: pointer;
}

.message-media-video {
    max-width: 250px;
    border-radius: 5px;
}

.message-media-audio {
    width: 220px;
}

.audio-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.audio-duration {
    font-size: 12px;
    color: #667781;
    font-weight: 500;
}

.message-document {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: rgba(0,0,0,0.05);
    border-radius: 5px;
    cursor: pointer;
    min-width: 180px;
}

.message-document i {
    font-size: 2rem;
    color: #8696a0;
}

.message-document .doc-info {
    flex: 1;
}

.message-document .doc-name {
    font-size: 0.85rem;
    font-weight: 500;
    word-break: break-all;
}

.message-document .doc-download {
    color: #25d366;
}

/* Context Menu */
.message-context-menu {
    position: absolute;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 1000;
    min-width: 150px;
    display: none;
}

.message-context-menu.show {
    display: block;
}

.message-context-menu .menu-item {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

.message-context-menu .menu-item:hover {
    background: #f5f5f5;
}

.message-context-menu .menu-item i {
    width: 18px;
    text-align: center;
}

/* Emoji Picker */
.emoji-picker {
    position: absolute;
    bottom: 100%;
    left: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    padding: 10px;
    display: none;
    z-index: 1000;
}

.emoji-picker.show {
    display: block;
}

.emoji-picker .emoji-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 5px;
}

.emoji-picker .emoji {
    font-size: 1.3rem;
    cursor: pointer;
    padding: 3px;
    text-align: center;
    border-radius: 3px;
}

.emoji-picker .emoji:hover {
    background: #f0f0f0;
}

/* Input */
.chat-input {
    padding: 10px;
    background: var(--header-bg);
    border-radius: 0 0 8px 8px;
    border-top: 1px solid #ddd;
}

.chat-input-reply {
    background: #e5f7e5;
    padding: 8px 12px;
    border-radius: 5px;
    margin-bottom: 8px;
    display: none;
    align-items: center;
    gap: 10px;
}

.chat-input-reply.show {
    display: flex;
}

.chat-input-reply .reply-text {
    flex: 1;
    font-size: 0.85rem;
    color: #667781;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-input-reply .reply-close {
    cursor: pointer;
    color: #8696a0;
}

.chat-input form {
    display: flex;
    gap: 8px;
    align-items: center;
}

.chat-input .input-icons {
    display: flex;
    gap: 3px;
    color: #8696a0;
}

.chat-input .input-icons .icon-btn {
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: background 0.2s;
}

.chat-input .input-icons .icon-btn:hover {
    background: #ddd;
    color: #075e54;
}

.chat-input input[type="text"] {
    flex: 1;
    border: none;
    border-radius: 20px;
    padding: 10px 15px;
    font-size: 0.9rem;
}

.chat-input input[type="text"]:focus {
    outline: none;
    box-shadow: none;
}

.chat-input .btn-send {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-input .btn-mic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #075e54;
    color: white;
    border: none;
}

.chat-input .btn-mic.recording {
    background: #dc3545;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Attachment Menu */
.attachment-menu {
    position: absolute;
    bottom: 100%;
    left: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    padding: 10px;
    display: none;
    z-index: 1000;
}

.attachment-menu.show {
    display: block;
}

.attachment-menu .attachment-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    cursor: pointer;
    border-radius: 5px;
}

.attachment-menu .attachment-item:hover {
    background: #f5f5f5;
}

.attachment-menu .attachment-item i {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.attachment-menu .attachment-item.img i { background: #7f66ff; }
.attachment-menu .attachment-item.video i { background: #ec407a; }
.attachment-menu .attachment-item.doc i { background: #5157ae; }
.attachment-menu .attachment-item.audio i { background: #ee6e25; }

.slot-disponivel {
    min-width: 150px;
    max-width: 200px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: calc(100vh - 250px);
    color: #adb5bd;
}

.slot-disponivel i {
    font-size: 2rem;
    margin-bottom: 10px;
}

.slot-disponivel span {
    font-size: 0.85rem;
}

/* Scrollbar */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.chats-container::-webkit-scrollbar {
    height: 8px;
}

.chats-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.chats-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

/* Image Modal */
.image-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
}

.image-modal.show {
    display: flex;
}

.image-modal img {
    max-width: 90%;
    max-height: 90%;
}

.image-modal .close-modal {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 2rem;
    cursor: pointer;
}

</style>
@stop

@section('content')
{{-- Header do Painel --}}
<div class="painel-header">
    <div class="info">
        <i class="fas fa-headset"></i>
        <div>
            <strong>Dashboard de Atendimento</strong>
            <span class="ml-3">{{ $user->name }} &mdash; {{ $slotsUsados }}/{{ $maxSlots }}</span>
        </div>
    </div>
    <div class="actions">
        <button class="btn btn-danger btn-sm" id="btnFinalizarTodas" {{ $slotsUsados == 0 ? 'disabled' : '' }}>
            <i class="fas fa-times-circle"></i> Finalizar Todas
        </button>
        <a href="{{ route('admin.fila') }}" class="btn btn-warning btn-sm">
            <i class="fas fa-users"></i> Fila <span class="badge badge-light">{{ $filaCount }}</span>
        </a>
        <button class="btn btn-light btn-sm" id="btnRefresh">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

{{-- Container de Chats --}}
<div class="chats-container">
    @foreach($conversas as $index => $conversa)
    @php
        $isGroup = $conversa->chat && $conversa->chat->chat_type === 'group';
    @endphp
    <div class="chat-column" data-conversa-id="{{ $conversa->id }}" data-is-group="{{ $isGroup ? '1' : '0' }}">
        {{-- Header --}}
        <div class="chat-column-header">
            <div class="avatar color-{{ ($index % 5) + 1 }}">
                @if($isGroup)
                    <i class="fas fa-users" style="font-size: 1rem;"></i>
                @else
                    {{ strtoupper(substr($conversa->cliente_nome ?? 'C', 0, 2)) }}
                @endif
            </div>
            <div class="info">
                <div class="name">
                    {{ $conversa->cliente_nome ?? 'Cliente' }}
                    @if($isGroup)
                        <span class="badge-group">Grupo</span>
                    @endif
                </div>
                <div class="number">{{ $conversa->cliente_numero }}</div>
            </div>
            <div class="actions">
                <button class="btn btn-sm btn-light btn-refresh-chat" title="Atualizar">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item btn-sincronizar-historico" href="#" data-id="{{ $conversa->id }}">
                            <i class="fas fa-history text-warning"></i> Buscar historico
                        </a>
                        <a class="dropdown-item btn-baixar-midias" href="#" data-id="{{ $conversa->id }}">
                            <i class="fas fa-download text-info"></i> Baixar midias
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item btn-marcar-lido" href="#" data-id="{{ $conversa->id }}">
                            <i class="fas fa-check-double text-primary"></i> Marcar como lido
                        </a>
                        <a class="dropdown-item btn-finalizar" href="#" data-id="{{ $conversa->id }}">
                            <i class="fas fa-check text-success"></i> Finalizar
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.conversas.show', $conversa) }}">
                            <i class="fas fa-info-circle text-info"></i> Detalhes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="chat-messages" id="messages-{{ $conversa->id }}">
            @if($conversa->chat)
                @php
                    $lastDate = null;
                    // Inverte para ordem cronológica (antigas primeiro, novas no final)
                    $mensagens = $conversa->chat->messages->reverse();
                @endphp
                @foreach($mensagens as $msg)
                @php $currentDate = $msg->message_date; @endphp
                @if($currentDate !== $lastDate)
                    <div class="message-date-separator">
                        <span>{{ $currentDate }}</span>
                    </div>
                    @php $lastDate = $currentDate; @endphp
                @endif
                <div class="message {{ $msg->is_from_me ? 'sent' : 'received' }}"
                     data-message-key="{{ $msg->message_key }}"
                     data-message-text="{{ $msg->message_text }}">
                    <div class="message-bubble">
                        @if(!$msg->is_from_me && $isGroup && $msg->sender_name)
                            <div class="message-sender">{{ $msg->sender_name }}</div>
                        @endif

                        @if($msg->quoted_text)
                            <div class="message-quoted">{{ Str::limit($msg->quoted_text, 100) }}</div>
                        @endif

                        @if($msg->is_deleted)
                            <div class="message-text message-deleted">
                                <i class="fas fa-ban"></i> Mensagem apagada
                            </div>
                        @elseif($msg->message_type === 'image')
                            @if($msg->media_url)
                                <img src="{{ $msg->media_url }}" class="message-media-img" alt="Imagem" onclick="openImageModal(this.src)">
                            @else
                                <div class="message-text"><i class="fas fa-image"></i> Imagem</div>
                            @endif
                            @if($msg->message_text)
                                <div class="message-text">{!! nl2br(e($msg->message_text)) !!}</div>
                            @endif
                        @elseif($msg->message_type === 'video')
                            @if($msg->media_url)
                                <video class="message-media-video" controls>
                                    <source src="{{ $msg->media_url }}" type="{{ $msg->media_mime_type ?? 'video/mp4' }}">
                                </video>
                            @else
                                <div class="message-text"><i class="fas fa-video"></i> Video</div>
                            @endif
                            @if($msg->message_text)
                                <div class="message-text">{!! nl2br(e($msg->message_text)) !!}</div>
                            @endif
                        @elseif($msg->message_type === 'audio')
                            @if($msg->media_url)
                                <div class="audio-container">
                                    <audio class="message-media-audio" controls>
                                        <source src="{{ $msg->media_url }}" type="{{ $msg->media_mime_type ?? 'audio/ogg' }}">
                                    </audio>
                                    @if($msg->media_duration)
                                        <span class="audio-duration">{{ floor($msg->media_duration / 60) }}:{{ str_pad($msg->media_duration % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                    @endif
                                </div>
                            @else
                                <div class="message-text">
                                    <i class="fas fa-microphone"></i> Audio
                                    @if($msg->media_duration)
                                        <span class="audio-duration">{{ floor($msg->media_duration / 60) }}:{{ str_pad($msg->media_duration % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                    @endif
                                </div>
                            @endif
                        @elseif($msg->message_type === 'document')
                            <a href="{{ $msg->media_url }}" target="_blank" class="message-document" download>
                                <i class="fas fa-file-alt"></i>
                                <div class="doc-info">
                                    <div class="doc-name">{{ $msg->media_filename ?? $msg->message_text ?? 'Documento' }}</div>
                                </div>
                                <i class="fas fa-download doc-download"></i>
                            </a>
                        @elseif($msg->message_type === 'sticker')
                            @if($msg->media_url)
                                <img src="{{ $msg->media_url }}" class="message-media-img" alt="Sticker" style="max-width: 150px;">
                            @else
                                <div class="message-text"><i class="fas fa-sticky-note"></i> Sticker</div>
                            @endif
                        @else
                            <div class="message-text">{!! nl2br(e($msg->message_text)) !!}</div>
                        @endif

                        @if($msg->reactions && count($msg->reactions) > 0)
                            <div class="message-reactions">
                                @foreach($msg->reactions as $reaction)
                                    <span class="reaction">{{ $reaction['emoji'] }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="message-time">
                            @if($msg->is_edited)
                                <span class="message-edited">editado</span>
                            @endif
                            {{ $msg->message_time }}
                            @if($msg->is_from_me)
                                <i class="fas fa-check-double check"></i>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        {{-- Input --}}
        <div class="chat-input">
            <div class="chat-input-reply" id="reply-{{ $conversa->id }}">
                <i class="fas fa-reply"></i>
                <span class="reply-text"></span>
                <i class="fas fa-times reply-close" onclick="cancelReply({{ $conversa->id }})"></i>
            </div>

            <form class="form-enviar" data-conversa-id="{{ $conversa->id }}">
                @csrf
                <input type="hidden" name="quoted_message_id" id="quoted-{{ $conversa->id }}" value="">

                <div class="input-icons" style="position: relative;">
                    <div class="attachment-menu" id="attach-menu-{{ $conversa->id }}">
                        <div class="attachment-item img" onclick="triggerFileInput({{ $conversa->id }}, 'imagem')">
                            <i class="fas fa-image"></i>
                            <span>Imagem</span>
                        </div>
                        <div class="attachment-item video" onclick="triggerFileInput({{ $conversa->id }}, 'video')">
                            <i class="fas fa-video"></i>
                            <span>Video</span>
                        </div>
                        <div class="attachment-item doc" onclick="triggerFileInput({{ $conversa->id }}, 'documento')">
                            <i class="fas fa-file-alt"></i>
                            <span>Documento</span>
                        </div>
                        <div class="attachment-item audio" onclick="triggerFileInput({{ $conversa->id }}, 'audio')">
                            <i class="fas fa-music"></i>
                            <span>Audio</span>
                        </div>
                    </div>
                    <i class="fas fa-paperclip icon-btn btn-attach" onclick="toggleAttachMenu({{ $conversa->id }})" title="Anexar"></i>
                    <i class="far fa-smile icon-btn btn-emoji" title="Emoji"></i>
                </div>

                <input type="text" name="mensagem" placeholder="Digite uma mensagem..." autocomplete="off">

                <button type="submit" class="btn btn-success btn-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
                <button type="button" class="btn-mic" onclick="toggleRecording(this, {{ $conversa->id }})" title="Gravar audio">
                    <i class="fas fa-microphone"></i>
                </button>
            </form>

            {{-- Hidden file inputs --}}
            <input type="file" id="file-imagem-{{ $conversa->id }}" accept="image/*" style="display:none" onchange="uploadFile({{ $conversa->id }}, 'imagem', this)">
            <input type="file" id="file-video-{{ $conversa->id }}" accept="video/*" style="display:none" onchange="uploadFile({{ $conversa->id }}, 'video', this)">
            <input type="file" id="file-documento-{{ $conversa->id }}" accept="*/*" style="display:none" onchange="uploadFile({{ $conversa->id }}, 'documento', this)">
            <input type="file" id="file-audio-{{ $conversa->id }}" accept="audio/*" style="display:none" onchange="uploadFile({{ $conversa->id }}, 'audio', this)">
        </div>
    </div>
    @endforeach

    {{-- Slots disponiveis --}}
    @for($i = 0; $i < $slotsDisponiveis; $i++)
    <div class="slot-disponivel">
        <i class="fas fa-comment-slash"></i>
        <span>Slot disponivel</span>
    </div>
    @endfor
</div>

{{-- Context Menu --}}
<div class="message-context-menu" id="contextMenu">
    <div class="menu-item" onclick="replyToMessage()"><i class="fas fa-reply"></i> Responder</div>
    <div class="menu-item" onclick="showEmojiPicker()"><i class="far fa-smile"></i> Reagir</div>
    <div class="menu-item menu-edit" onclick="editMessage()"><i class="fas fa-edit"></i> Editar</div>
    <div class="menu-item menu-delete" onclick="deleteMessage()"><i class="fas fa-trash"></i> Apagar</div>
</div>

{{-- Emoji Picker for Reactions --}}
<div class="emoji-picker" id="reactionPicker">
    <div class="emoji-grid">
        @foreach(['👍','❤️','😂','😮','😢','🙏','👏','🔥','🎉','💯'] as $emoji)
            <span class="emoji" onclick="sendReaction('{{ $emoji }}')">{{ $emoji }}</span>
        @endforeach
    </div>
</div>

{{-- Image Modal --}}
<div class="image-modal" id="imageModal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img src="" id="modalImage">
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentMessageKey = null;
let currentConversaId = null;
let currentMessageElement = null;
let mediaRecorder = null;
let audioChunks = [];

// Toast usando SweetAlert2
function showToast(message, type = 'info', duration = null) {
    const icons = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };

    const defaultDurations = {
        success: 3000,
        error: 5000,
        warning: 4000,
        info: 3000
    };

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icons[type] || 'info',
        title: message,
        showConfirmButton: false,
        timer: duration || defaultDurations[type] || 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
}

$(function() {
    // Scroll todas as mensagens para baixo
    $('.chat-messages').each(function() {
        this.scrollTop = this.scrollHeight;
    });

    // Context menu nas mensagens
    $(document).on('contextmenu', '.message-bubble', function(e) {
        e.preventDefault();

        var msg = $(this).closest('.message');
        currentMessageKey = msg.data('message-key');
        currentConversaId = msg.closest('.chat-column').data('conversa-id');
        currentMessageElement = msg;

        var isFromMe = msg.hasClass('sent');

        // Mostrar/esconder opcoes baseado em quem enviou
        $('.menu-edit, .menu-delete').toggle(isFromMe);

        var menu = $('#contextMenu');
        menu.css({
            top: e.pageY + 'px',
            left: e.pageX + 'px'
        }).addClass('show');
    });

    // Fechar menus ao clicar fora
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.message-context-menu').length) {
            $('#contextMenu').removeClass('show');
        }
        if (!$(e.target).closest('.emoji-picker').length && !$(e.target).hasClass('btn-emoji')) {
            $('#reactionPicker').removeClass('show');
        }
        if (!$(e.target).closest('.attachment-menu').length && !$(e.target).hasClass('btn-attach')) {
            $('.attachment-menu').removeClass('show');
        }
    });

    // Enviar mensagem via AJAX
    $('.form-enviar').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var conversaId = form.data('conversa-id');
        var input = form.find('input[name="mensagem"]');
        var mensagem = input.val().trim();
        var quotedId = $('#quoted-' + conversaId).val();

        if (!mensagem) return;

        var btn = form.find('.btn-send');
        btn.prop('disabled', true);

        $.ajax({
            url: '/admin/painel/' + conversaId + '/enviar',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mensagem: mensagem,
                quoted_message_id: quotedId
            },
            success: function(response) {
                if (response.success && response.message) {
                    var container = $('#messages-' + conversaId);
                    container.append(buildMessageHtml(response.message));
                    container[0].scrollTop = container[0].scrollHeight;
                }
                input.val('');
                cancelReply(conversaId);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.error || 'Erro ao enviar mensagem', 'error');
            },
            complete: function() {
                btn.prop('disabled', false);
                input.focus();
            }
        });
    });

    // Enviar ao digitar (typing indicator)
    var typingTimeout;
    $('.form-enviar input[name="mensagem"]').on('input', function() {
        var conversaId = $(this).closest('form').data('conversa-id');
        clearTimeout(typingTimeout);

        $.post('/admin/painel/' + conversaId + '/digitando', { _token: '{{ csrf_token() }}' });

        typingTimeout = setTimeout(function() {}, 3000);
    });

    // Finalizar conversa
    $('.btn-finalizar').on('click', function(e) {
        e.preventDefault();
        var conversaId = $(this).data('id');

        if (!confirm('Finalizar esta conversa?')) return;

        $.ajax({
            url: '/admin/painel/' + conversaId + '/finalizar',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                location.reload();
            },
            error: function() {
                showToast('Erro ao finalizar conversa', 'error');
            }
        });
    });

    // Marcar como lido
    $('.btn-marcar-lido').on('click', function(e) {
        e.preventDefault();
        var conversaId = $(this).data('id');

        $.post('/admin/painel/' + conversaId + '/marcar-lido', { _token: '{{ csrf_token() }}' });
    });

    // Finalizar todas
    $('#btnFinalizarTodas').on('click', function() {
        if (!confirm('Finalizar TODAS as conversas?')) return;

        var conversas = $('.chat-column');
        var total = conversas.length;
        var done = 0;

        conversas.each(function() {
            var id = $(this).data('conversa-id');
            $.ajax({
                url: '/admin/painel/' + id + '/finalizar',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                complete: function() {
                    done++;
                    if (done >= total) {
                        location.reload();
                    }
                }
            });
        });
    });

    // Refresh
    $('#btnRefresh').on('click', function() {
        location.reload();
    });

    // Refresh individual chat
    $('.btn-refresh-chat').on('click', function() {
        refreshChat($(this).closest('.chat-column').data('conversa-id'));
    });

    // Sincronizar histórico
    $('.btn-sincronizar-historico').on('click', function(e) {
        e.preventDefault();
        var conversaId = $(this).data('id');
        var btn = $(this);

        Swal.fire({
            title: 'Buscar Historico',
            html: `
                <p>Quantas mensagens deseja buscar?</p>
                <input type="number" id="swal-limit" class="swal2-input" value="500" min="50" max="5000" placeholder="Quantidade">
                <p style="font-size:0.75em;color:#999;margin-top:5px;">Quanto maior, mais mensagens antigas serao importadas</p>
                <p style="margin-top:15px;font-size:0.9em;color:#666;">Numero real do WhatsApp (opcional):</p>
                <input type="text" id="swal-numero" class="swal2-input" placeholder="Ex: 5544999887766">
                <p style="font-size:0.75em;color:#999;">Use se o contato usa LID em vez do numero</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Buscar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#25d366',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                var limit = document.getElementById('swal-limit').value || 100;
                var numero = document.getElementById('swal-numero').value || '';
                return $.ajax({
                    url: '/admin/painel/' + conversaId + '/sincronizar-historico',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        limit: limit,
                        numero_real: numero
                    }
                }).then(response => response)
                .catch(error => {
                    Swal.showValidationMessage(error.responseJSON?.error || 'Erro ao sincronizar');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                showToast(`Importadas ${result.value.imported} mensagens (${result.value.skipped} ja existiam)`, 'success');
                refreshChat(conversaId);
            }
        });
    });

    // Baixar mídias pendentes
    $('.btn-baixar-midias').on('click', function(e) {
        e.preventDefault();
        var conversaId = $(this).data('id');

        Swal.fire({
            title: 'Baixar Midias',
            text: 'Isso vai baixar imagens, audios e videos que ainda nao foram baixados.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Baixar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#17a2b8',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '/admin/painel/' + conversaId + '/baixar-midias',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' }
                }).then(response => response)
                .catch(error => {
                    Swal.showValidationMessage(error.responseJSON?.error || 'Erro ao baixar');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                var msg = `Baixadas ${result.value.downloaded} midias`;
                if (result.value.failed > 0) {
                    msg += ` (${result.value.failed} falharam)`;
                }
                if (result.value.pending > 0) {
                    msg += `. Ainda restam ${result.value.pending} pendentes.`;
                }
                showToast(msg, result.value.downloaded > 0 ? 'success' : 'warning');
                refreshChat(conversaId);
            }
        });
    });

    // Auto-refresh every 10 seconds
    setInterval(function() {
        $('.chat-column').each(function() {
            var conversaId = $(this).data('conversa-id');
            var container = $('#messages-' + conversaId);
            var isGroup = $(this).data('is-group') === 1;

            $.ajax({
                url: '/admin/painel/' + conversaId + '/mensagens',
                method: 'GET',
                success: function(response) {
                    var currentCount = container.find('.message').length;
                    if (response.messages.length > currentCount) {
                        container.empty();
                        var lastDate = null;
                        response.messages.forEach(function(msg) {
                            if (msg.message_date && msg.message_date !== lastDate) {
                                container.append('<div class="message-date-separator"><span>' + msg.message_date + '</span></div>');
                                lastDate = msg.message_date;
                            }
                            container.append(buildMessageHtml(msg, isGroup));
                        });
                        container[0].scrollTop = container[0].scrollHeight;
                    }
                }
            });
        });
    }, 10000);
});

function refreshChat(conversaId) {
    var container = $('#messages-' + conversaId);
    var isGroup = container.closest('.chat-column').data('is-group') === 1;

    $.ajax({
        url: '/admin/painel/' + conversaId + '/mensagens',
        method: 'GET',
        success: function(response) {
            container.empty();
            var lastDate = null;
            response.messages.forEach(function(msg) {
                // Adicionar separador de data se mudou
                if (msg.message_date && msg.message_date !== lastDate) {
                    container.append('<div class="message-date-separator"><span>' + msg.message_date + '</span></div>');
                    lastDate = msg.message_date;
                }
                container.append(buildMessageHtml(msg, isGroup));
            });
            container[0].scrollTop = container[0].scrollHeight;
        }
    });
}

function buildMessageHtml(msg, isGroup) {
    var typeClass = msg.is_from_me ? 'sent' : 'received';
    var content = '';

    // Sender name for groups
    var senderHtml = '';
    if (!msg.is_from_me && isGroup && msg.sender_name) {
        senderHtml = '<div class="message-sender">' + escapeHtml(msg.sender_name) + '</div>';
    }

    // Quoted message
    var quotedHtml = '';
    if (msg.quoted_text) {
        quotedHtml = '<div class="message-quoted">' + escapeHtml(msg.quoted_text).substring(0, 100) + '</div>';
    }

    // Content based on type
    if (msg.is_deleted) {
        content = '<div class="message-text message-deleted"><i class="fas fa-ban"></i> Mensagem apagada</div>';
    } else if (msg.message_type === 'image') {
        content = msg.media_url
            ? '<img src="' + msg.media_url + '" class="message-media-img" onclick="openImageModal(this.src)">'
            : '<div class="message-text"><i class="fas fa-image"></i> Imagem</div>';
        if (msg.message_text) {
            content += '<div class="message-text">' + escapeHtml(msg.message_text).replace(/\n/g, '<br>') + '</div>';
        }
    } else if (msg.message_type === 'video') {
        content = msg.media_url
            ? '<video class="message-media-video" controls><source src="' + msg.media_url + '"></video>'
            : '<div class="message-text"><i class="fas fa-video"></i> Video</div>';
    } else if (msg.message_type === 'audio') {
        var durationStr = '';
        if (msg.media_duration) {
            var mins = Math.floor(msg.media_duration / 60);
            var secs = (msg.media_duration % 60).toString().padStart(2, '0');
            durationStr = '<span class="audio-duration">' + mins + ':' + secs + '</span>';
        }
        content = msg.media_url
            ? '<div class="audio-container"><audio class="message-media-audio" controls><source src="' + msg.media_url + '"></audio>' + durationStr + '</div>'
            : '<div class="message-text"><i class="fas fa-microphone"></i> Audio' + durationStr + '</div>';
    } else if (msg.message_type === 'document') {
        var docName = msg.media_filename || msg.message_text || 'Documento';
        content = '<a href="' + (msg.media_url || '#') + '" target="_blank" class="message-document" download>' +
            '<i class="fas fa-file-alt"></i>' +
            '<div class="doc-info"><div class="doc-name">' + escapeHtml(docName) + '</div></div>' +
            '<i class="fas fa-download doc-download"></i></a>';
    } else {
        content = '<div class="message-text">' + escapeHtml(msg.message_text || '').replace(/\n/g, '<br>') + '</div>';
    }

    // Reactions
    var reactionsHtml = '';
    if (msg.reactions && msg.reactions.length > 0) {
        reactionsHtml = '<div class="message-reactions">';
        msg.reactions.forEach(function(r) {
            reactionsHtml += '<span class="reaction">' + r.emoji + '</span>';
        });
        reactionsHtml += '</div>';
    }

    var editedHtml = msg.is_edited ? '<span class="message-edited">editado</span>' : '';
    var checkHtml = msg.is_from_me ? '<i class="fas fa-check-double check"></i>' : '';

    return '<div class="message ' + typeClass + '" data-message-key="' + msg.message_key + '" data-message-text="' + escapeHtml(msg.message_text || '') + '">' +
        '<div class="message-bubble">' +
        senderHtml +
        quotedHtml +
        content +
        reactionsHtml +
        '<div class="message-time">' + editedHtml + msg.created_at + ' ' + checkHtml + '</div>' +
        '</div></div>';
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Context menu actions
function replyToMessage() {
    $('#contextMenu').removeClass('show');
    var text = currentMessageElement.data('message-text') || '[Midia]';

    $('#reply-' + currentConversaId).addClass('show');
    $('#reply-' + currentConversaId + ' .reply-text').text(text.substring(0, 50));
    $('#quoted-' + currentConversaId).val(currentMessageKey);

    $('[data-conversa-id="' + currentConversaId + '"] input[name="mensagem"]').focus();
}

function cancelReply(conversaId) {
    $('#reply-' + conversaId).removeClass('show');
    $('#quoted-' + conversaId).val('');
}

function showEmojiPicker() {
    $('#contextMenu').removeClass('show');
    var pos = currentMessageElement.offset();
    $('#reactionPicker').css({
        top: (pos.top - 50) + 'px',
        left: pos.left + 'px'
    }).addClass('show');
}

function sendReaction(emoji) {
    $('#reactionPicker').removeClass('show');

    $.post('/admin/painel/' + currentConversaId + '/reagir', {
        _token: '{{ csrf_token() }}',
        message_key: currentMessageKey,
        emoji: emoji
    }).done(function() {
        refreshChat(currentConversaId);
    });
}

function editMessage() {
    $('#contextMenu').removeClass('show');
    var currentText = currentMessageElement.data('message-text');
    var newText = prompt('Editar mensagem:', currentText);

    if (newText && newText !== currentText) {
        $.post('/admin/painel/' + currentConversaId + '/editar', {
            _token: '{{ csrf_token() }}',
            message_key: currentMessageKey,
            new_text: newText
        }).done(function(response) {
            if (response.success) {
                refreshChat(currentConversaId);
            }
        }).fail(function(xhr) {
            showToast(xhr.responseJSON?.error || 'Erro ao editar mensagem', 'error');
        });
    }
}

function deleteMessage() {
    $('#contextMenu').removeClass('show');

    if (!confirm('Apagar esta mensagem para todos?')) return;

    $.post('/admin/painel/' + currentConversaId + '/deletar', {
        _token: '{{ csrf_token() }}',
        message_key: currentMessageKey
    }).done(function(response) {
        if (response.success) {
            showToast('Mensagem apagada', 'success');
            refreshChat(currentConversaId);
        }
    }).fail(function(xhr) {
        showToast(xhr.responseJSON?.error || 'Erro ao apagar mensagem', 'error');
    });
}

// Attachment menu
function toggleAttachMenu(conversaId) {
    $('.attachment-menu').removeClass('show');
    $('#attach-menu-' + conversaId).toggleClass('show');
}

function triggerFileInput(conversaId, type) {
    $('.attachment-menu').removeClass('show');
    $('#file-' + type + '-' + conversaId).click();
}

function uploadFile(conversaId, type, input) {
    if (!input.files || !input.files[0]) return;

    var file = input.files[0];
    var formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append(type === 'imagem' ? 'imagem' : type === 'video' ? 'video' : type === 'documento' ? 'documento' : 'audio', file);

    var endpoint = type === 'imagem' ? 'enviar-imagem' :
                   type === 'video' ? 'enviar-video' :
                   type === 'documento' ? 'enviar-documento' : 'enviar-audio';

    $.ajax({
        url: '/admin/painel/' + conversaId + '/' + endpoint,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success && response.message) {
                var container = $('#messages-' + conversaId);
                container.append(buildMessageHtml(response.message));
                container[0].scrollTop = container[0].scrollHeight;
            }
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.error || 'Erro ao enviar arquivo', 'error');
        }
    });

    input.value = '';
}

// Audio recording
function toggleRecording(btn, conversaId) {
    // Verificar se a API de gravacao esta disponivel (requer HTTPS)
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showToast('Gravacao de audio nao disponivel. Acesse via HTTPS ou use localhost.', 'warning', 6000);
        return;
    }

    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        $(btn).removeClass('recording');
        $(btn).find('i').removeClass('fa-stop').addClass('fa-microphone');
    } else {
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(function(stream) {
                audioChunks = [];
                mediaRecorder = new MediaRecorder(stream);

                mediaRecorder.ondataavailable = function(e) {
                    audioChunks.push(e.data);
                };

                mediaRecorder.onstop = function() {
                    var audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    var formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('audio', audioBlob, 'audio.webm');

                    $.ajax({
                        url: '/admin/painel/' + conversaId + '/enviar-audio',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.message) {
                                var container = $('#messages-' + conversaId);
                                container.append(buildMessageHtml(response.message));
                                container[0].scrollTop = container[0].scrollHeight;
                            }
                        },
                        error: function(xhr) {
                            showToast(xhr.responseJSON?.error || 'Erro ao enviar audio', 'error');
                        }
                    });

                    stream.getTracks().forEach(track => track.stop());
                };

                mediaRecorder.start();
                $(btn).addClass('recording');
                $(btn).find('i').removeClass('fa-microphone').addClass('fa-stop');
                showToast('Gravando audio... Clique novamente para parar', 'info', 2000);
            })
            .catch(function(err) {
                showToast('Erro ao acessar microfone: ' + err.message, 'error');
            });
    }
}

// Image modal
function openImageModal(src) {
    $('#modalImage').attr('src', src);
    $('#imageModal').addClass('show');
}

function closeImageModal() {
    $('#imageModal').removeClass('show');
}
</script>
@stop
