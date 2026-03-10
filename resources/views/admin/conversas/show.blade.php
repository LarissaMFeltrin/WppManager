@extends('adminlte::page')

@section('title', 'Conversa')

@section('content_header')
    <h1><i class="fas fa-comment"></i> Detalhes da Conversa</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informacoes</h3>
            </div>
            <div class="card-body">
                <p><strong>Cliente:</strong> {{ $conversa->cliente_nome ?? 'N/A' }}</p>
                <p><strong>Numero:</strong> {{ $conversa->cliente_numero }}</p>
                <p><strong>Atendente:</strong> {{ $conversa->atendente?->name ?? 'N/A' }}</p>
                <p><strong>Instancia:</strong> {{ $conversa->account?->session_name ?? 'N/A' }}</p>
                <p><strong>Status:</strong>
                    @if($conversa->status === 'aguardando')
                        <span class="badge badge-warning">Aguardando</span>
                    @elseif($conversa->status === 'em_atendimento')
                        <span class="badge badge-primary">Em Atendimento</span>
                    @else
                        <span class="badge badge-success">Finalizada</span>
                    @endif
                </p>
                <hr>
                <p><strong>Iniciada:</strong> {{ $conversa->iniciada_em?->format('d/m/Y H:i') }}</p>
                <p><strong>Atendida:</strong> {{ $conversa->atendida_em?->format('d/m/Y H:i') ?? '-' }}</p>
                <p><strong>Finalizada:</strong> {{ $conversa->finalizada_em?->format('d/m/Y H:i') ?? '-' }}</p>

                @if($conversa->notas)
                    <hr>
                    <p><strong>Notas:</strong></p>
                    <p>{{ $conversa->notas }}</p>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.conversas.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Mensagens</h3>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto; background: #f4f6f9;">
                @if($conversa->chat && $conversa->chat->messages->count() > 0)
                    @foreach($conversa->chat->messages as $msg)
                        <div class="mb-2 p-2 rounded {{ $msg->from_me ? 'bg-success text-white ml-5' : 'bg-white mr-5' }}">
                            <div>{{ $msg->content }}</div>
                            <small class="{{ $msg->from_me ? 'text-light' : 'text-muted' }}">
                                {{ $msg->created_at->format('d/m H:i') }}
                            </small>
                        </div>
                    @endforeach
                @else
                    <p class="text-center text-muted py-4">Nenhuma mensagem</p>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
