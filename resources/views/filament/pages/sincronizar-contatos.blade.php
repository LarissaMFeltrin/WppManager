<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-gray-700">
                <h3 class="text-lg font-medium">Sincronizar Contatos das Instancias</h3>
                <p class="text-sm text-gray-500 mt-1">Importe os contatos do WhatsApp para o sistema</p>
            </div>

            <div class="divide-y dark:divide-gray-700">
                @forelse($instancias as $instancia)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $instancia['is_connected'] ? 'bg-green-100' : 'bg-red-100' }}">
                                <x-heroicon-o-device-phone-mobile class="w-5 h-5 {{ $instancia['is_connected'] ? 'text-green-600' : 'text-red-600' }}" />
                            </div>
                            <div>
                                <div class="font-medium">{{ $instancia['session_name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $instancia['phone_number'] }}</div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ $instancia['contacts_count'] }}</div>
                            <div class="text-xs text-gray-500">contatos</div>
                        </div>

                        <div class="text-center">
                            <div class="text-sm text-gray-500">Ultima sincronizacao</div>
                            <div class="text-sm">{{ $instancia['last_full_sync'] ?? 'Nunca' }}</div>
                        </div>

                        <div>
                            @if($instancia['is_connected'])
                                <x-filament::button
                                    wire:click="sincronizar({{ $instancia['id'] }})"
                                    wire:loading.attr="disabled"
                                    icon="heroicon-o-arrow-path"
                                >
                                    <span wire:loading.remove wire:target="sincronizar({{ $instancia['id'] }})">Sincronizar</span>
                                    <span wire:loading wire:target="sincronizar({{ $instancia['id'] }})">Sincronizando...</span>
                                </x-filament::button>
                            @else
                                <span class="text-sm text-red-500">Desconectado</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        Nenhuma instancia cadastrada
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
