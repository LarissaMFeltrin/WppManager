@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
{{-- Cards superiores --}}
<div class="row">
    {{-- Total de Chats --}}
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-comments"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total de Chats</span>
                <span class="info-box-number">{{ $stats['total_chats'] }}</span>
            </div>
        </div>
    </div>

    {{-- Mensagens Hoje --}}
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-envelope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Mensagens Hoje</span>
                <span class="info-box-number">{{ $stats['mensagens_hoje'] }}</span>
            </div>
        </div>
    </div>

    {{-- Instancias Online --}}
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-mobile-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Instancias Online</span>
                <span class="info-box-number">{{ $stats['instancias_online'] }}</span>
            </div>
        </div>
    </div>

    {{-- Conversas Ativas --}}
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-comment-dots"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Conversas Ativas</span>
                <span class="info-box-number">{{ $stats['conversas_ativas'] }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Ultimas Mensagens --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-envelope"></i> Ultimas Mensagens</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Chat</th>
                            <th>Mensagem</th>
                            <th>Tipo</th>
                            <th>Direcao</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ultimasMensagens as $msg)
                        <tr>
                            <td>{{ $msg->chat?->chat_name ?? '-' }}</td>
                            <td>{{ Str::limit($msg->message_text ?? '[midia]', 40) }}</td>
                            <td><span class="badge badge-secondary">{{ $msg->message_type ?? 'text' }}</span></td>
                            <td>
                                @if($msg->is_from_me)
                                    <span class="badge badge-success"><i class="fas fa-arrow-up"></i> Enviada</span>
                                @else
                                    <span class="badge badge-info"><i class="fas fa-arrow-down"></i> Recebida</span>
                                @endif
                            </td>
                            <td>{{ $msg->created_at?->format('d/m/Y, H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nenhuma mensagem</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Instancias WhatsApp --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fab fa-whatsapp"></i> Instancias WhatsApp</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-tool">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($instancias as $instancia)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $instancia->session_name }}</strong>
                            <br>
                            <small class="text-muted">{{ $instancia->owner_jid ? explode('@', $instancia->owner_jid)[0] : '-' }}</small>
                        </div>
                        <div>
                            @if($instancia->is_connected)
                                <span class="badge badge-success"><i class="fas fa-circle"></i> Online</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-circle"></i> Offline</span>
                            @endif
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted">
                        Nenhuma instancia cadastrada
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@stop
