@extends('adminlte::page')

@section('title', 'Novo Usuario')

@section('content_header')
    <h1><i class="fas fa-user-plus"></i> Novo Usuario</h1>
@stop

@section('content')
<div class="card">
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">E-mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">Senha <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password_confirmation">Confirmar Senha <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="empresa_id">Empresa</label>
                        <select name="empresa_id" id="empresa_id" class="form-control select2">
                            <option value="">Selecione...</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ old('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                    {{ $empresa->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="role">Perfil <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="agent" {{ old('role', 'agent') === 'agent' ? 'selected' : '' }}>Agente</option>
                            <option value="supervisor" {{ old('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="status_atendimento">Status</label>
                        <select name="status_atendimento" id="status_atendimento" class="form-control">
                            <option value="offline">Offline</option>
                            <option value="online">Online</option>
                            <option value="ocupado">Ocupado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row" id="agent-config">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="max_conversas">Max. Conversas</label>
                        <input type="number" name="max_conversas" id="max_conversas" class="form-control"
                               value="{{ old('max_conversas', 5) }}" min="1" max="50">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="whatsapp_accounts">Instancias WhatsApp</label>
                        <select name="whatsapp_accounts[]" id="whatsapp_accounts" class="form-control select2" multiple>
                            @foreach($whatsappAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->session_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</div>
@stop

@section('js')
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap4' });

    function toggleAgentConfig() {
        if ($('#role').val() === 'agent') {
            $('#agent-config').show();
        } else {
            $('#agent-config').hide();
        }
    }

    $('#role').change(toggleAgentConfig);
    toggleAgentConfig();
});
</script>
@stop
