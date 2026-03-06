<div class="p-4">
    <div class="text-center mb-4">
        <p class="text-gray-600 mb-4">
            Para conectar a instância <strong>{{ $record->session_name }}</strong>,
            acesse o Manager da Evolution API:
        </p>

        <a href="http://localhost:8085/manager"
           target="_blank"
           class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            Abrir Evolution Manager
        </a>
    </div>

    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 mt-4">
        <h4 class="font-semibold mb-2">Instruções:</h4>
        <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <li>Clique no botão acima para abrir o Manager</li>
            <li>Encontre a instância <strong>{{ $record->session_name }}</strong></li>
            <li>Clique em "Connect" para gerar o QR Code</li>
            <li>Escaneie com seu WhatsApp</li>
            <li>Volte aqui e feche este modal</li>
        </ol>
    </div>

    <div class="mt-4 text-center">
        <p class="text-xs text-gray-400">
            Credenciais do Manager: use a API Key configurada no sistema
        </p>
    </div>
</div>
