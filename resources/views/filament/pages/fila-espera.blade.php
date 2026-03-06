<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b dark:border-gray-700 bg-yellow-50 dark:bg-yellow-900/20">
                <h2 class="text-lg font-medium text-yellow-800 dark:text-yellow-200">
                    Conversas Aguardando Atendimento ({{ count($conversasAguardando) }})
                </h2>
            </div>

            <div class="divide-y dark:divide-gray-700">
                @forelse($conversasAguardando as $conversa)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <x-heroicon-o-user class="w-6 h-6 text-gray-500" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $conversa['cliente_nome'] ?? 'Cliente' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $conversa['cliente_numero'] }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center px-4">
                            <div class="text-xs text-gray-500">Instância</div>
                            <div class="text-sm font-medium">{{ $conversa['account']['session_name'] ?? '-' }}</div>
                        </div>

                        <div class="text-center px-4">
                            <div class="text-xs text-gray-500">Aguardando há</div>
                            <div class="text-sm font-medium text-yellow-600">
                                @if(isset($conversa['cliente_aguardando_desde']))
                                    {{ \Carbon\Carbon::parse($conversa['cliente_aguardando_desde'])->diffForHumans(null, true) }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        <div>
                            <x-filament::button
                                wire:click="atenderConversa({{ $conversa['id'] }})"
                                color="success"
                                icon="heroicon-o-phone"
                            >
                                Atender
                            </x-filament::button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                        <p>Nenhuma conversa aguardando</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 10 segundos
        setInterval(() => {
            @this.loadConversas();
        }, 10000);
    </script>
</x-filament-panels::page>
