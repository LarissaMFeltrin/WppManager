<x-filament-panels::page>
    <div class="grid grid-cols-12 gap-4 h-[calc(100vh-200px)]">
        {{-- Sidebar: Lista de Conversas --}}
        <div class="col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden flex flex-col">
            {{-- Abas --}}
            <div class="flex border-b dark:border-gray-700">
                <button
                    wire:click="$set('activeTab', 'fila')"
                    class="flex-1 py-3 px-4 text-sm font-medium {{ ($activeTab ?? 'fila') === 'fila' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-700' }}"
                >
                    Fila ({{ count($conversasAguardando) }})
                </button>
                <button
                    wire:click="$set('activeTab', 'minhas')"
                    class="flex-1 py-3 px-4 text-sm font-medium {{ ($activeTab ?? 'fila') === 'minhas' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-700' }}"
                >
                    Minhas ({{ count($minhasConversas) }})
                </button>
            </div>

            {{-- Lista --}}
            <div class="flex-1 overflow-y-auto">
                @php $conversas = ($activeTab ?? 'fila') === 'fila' ? $conversasAguardando : $minhasConversas; @endphp

                @forelse($conversas as $conv)
                    <div
                        wire:click="{{ ($activeTab ?? 'fila') === 'fila' ? 'atenderConversa(' . $conv['id'] . ')' : 'selectConversa(' . $conv['id'] . ')' }}"
                        class="p-4 border-b dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 {{ $conversaId == $conv['id'] ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}"
                    >
                        <div class="flex items-center justify-between">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $conv['cliente_nome'] ?? 'Desconhecido' }}
                            </div>
                            @if(($activeTab ?? 'fila') === 'fila')
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Aguardando</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $conv['cliente_numero'] }}
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ $conv['account']['session_name'] ?? '' }}
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500">
                        Nenhuma conversa
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Chat Area --}}
        <div class="col-span-9 bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col overflow-hidden">
            @if($conversa)
                {{-- Header do Chat --}}
                <div class="p-4 border-b dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ $conversa->cliente_nome ?? 'Cliente' }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $conversa->cliente_numero }} - {{ $conversa->account?->session_name }}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <x-filament::button
                            wire:click="devolverConversa"
                            color="warning"
                            size="sm"
                        >
                            Devolver
                        </x-filament::button>
                        <x-filament::button
                            wire:click="finalizarConversa"
                            color="success"
                            size="sm"
                        >
                            Finalizar
                        </x-filament::button>
                    </div>
                </div>

                {{-- Mensagens --}}
                <div
                    class="flex-1 overflow-y-auto p-4 space-y-3"
                    id="messages-container"
                    wire:poll.5s="loadMessages"
                >
                    @foreach($messages as $message)
                        <div class="flex {{ $message['is_from_me'] ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[70%] rounded-lg px-4 py-2 {{ $message['is_from_me'] ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' }}">
                                @if($message['message_type'] !== 'text')
                                    <div class="text-xs opacity-75 mb-1">
                                        [{{ ucfirst($message['message_type']) }}]
                                    </div>
                                @endif
                                <div class="break-words">
                                    {{ $message['message_text'] ?? '[Mídia]' }}
                                </div>
                                <div class="text-xs mt-1 {{ $message['is_from_me'] ? 'text-primary-100' : 'text-gray-400' }}">
                                    {{ \Carbon\Carbon::createFromTimestamp($message['timestamp'])->format('H:i') }}
                                    @if($message['is_from_me'])
                                        @if($message['status'] === 'read')
                                            <x-heroicon-s-check class="inline w-3 h-3 text-blue-300" />
                                            <x-heroicon-s-check class="inline w-3 h-3 -ml-2 text-blue-300" />
                                        @elseif($message['status'] === 'delivered')
                                            <x-heroicon-s-check class="inline w-3 h-3" />
                                            <x-heroicon-s-check class="inline w-3 h-3 -ml-2" />
                                        @else
                                            <x-heroicon-s-check class="inline w-3 h-3" />
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Input de Mensagem --}}
                <div class="p-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <form wire:submit="sendMessage" class="flex gap-2">
                        <input
                            type="text"
                            wire:model="messageText"
                            placeholder="Digite sua mensagem..."
                            class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                            autofocus
                        >
                        <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                            Enviar
                        </x-filament::button>
                    </form>
                </div>
            @else
                {{-- Estado Vazio --}}
                <div class="flex-1 flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <x-heroicon-o-chat-bubble-left-right class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                        <p>Selecione uma conversa para iniciar o atendimento</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-scroll para última mensagem
        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Scroll inicial e após atualizações
        document.addEventListener('livewire:initialized', scrollToBottom);
        Livewire.hook('message.processed', scrollToBottom);

        // Escutar eventos WebSocket
        if (window.Echo) {
            @if($conversa && $conversa->account)
            window.Echo.private('account.{{ $conversa->account->id }}')
                .listen('.message.new', (e) => {
                    @this.loadMessages();
                    @this.loadConversas();
                });
            @endif
        }
    </script>
    @endpush
</x-filament-panels::page>
