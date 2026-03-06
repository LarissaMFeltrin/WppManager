<div class="space-y-4">
    <div>
        <label class="text-sm font-medium text-gray-500">Mensagem</label>
        <p class="mt-1 text-gray-900 dark:text-white">{{ $log->mensagem }}</p>
    </div>

    <div>
        <label class="text-sm font-medium text-gray-500">Nivel</label>
        <p class="mt-1">
            <span class="px-2 py-1 rounded text-xs {{ $log->nivel === 'error' ? 'bg-red-100 text-red-800' : ($log->nivel === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                {{ strtoupper($log->nivel) }}
            </span>
        </p>
    </div>

    <div>
        <label class="text-sm font-medium text-gray-500">Data</label>
        <p class="mt-1 text-gray-900 dark:text-white">{{ $log->criada_em?->format('d/m/Y H:i:s') }}</p>
    </div>

    <div>
        <label class="text-sm font-medium text-gray-500">IP</label>
        <p class="mt-1 text-gray-900 dark:text-white">{{ $log->ip_origem ?? '-' }}</p>
    </div>

    <div>
        <label class="text-sm font-medium text-gray-500">User Agent</label>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $log->user_agent ?? '-' }}</p>
    </div>

    @if($log->dados)
        <div>
            <label class="text-sm font-medium text-gray-500">Dados</label>
            <pre class="mt-1 p-3 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto max-h-64">{{ json_encode($log->dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
