<x-filament-panels::page>
    <div class="space-y-6" wire:poll.30s="loadData">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center gap-4">
                <div class="p-3 bg-blue-500 rounded-lg">
                    <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-white" />
                </div>
                <div>
                    <div class="text-sm text-gray-500">Total de Chats</div>
                    <div class="text-2xl font-bold">{{ $stats['total_chats'] ?? 0 }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center gap-4">
                <div class="p-3 bg-green-500 rounded-lg">
                    <x-heroicon-o-envelope class="w-8 h-8 text-white" />
                </div>
                <div>
                    <div class="text-sm text-gray-500">Mensagens Hoje</div>
                    <div class="text-2xl font-bold">{{ $stats['mensagens_hoje'] ?? 0 }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center gap-4">
                <div class="p-3 bg-yellow-500 rounded-lg">
                    <x-heroicon-o-device-phone-mobile class="w-8 h-8 text-white" />
                </div>
                <div>
                    <div class="text-sm text-gray-500">Instancias Online</div>
                    <div class="text-2xl font-bold">{{ $stats['instancias_online'] ?? 0 }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center gap-4">
                <div class="p-3 bg-red-500 rounded-lg">
                    <x-heroicon-o-chat-bubble-oval-left-ellipsis class="w-8 h-8 text-white" />
                </div>
                <div>
                    <div class="text-sm text-gray-500">Conversas Ativas</div>
                    <div class="text-2xl font-bold">{{ $stats['conversas_ativas'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Ultimas Mensagens --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b dark:border-gray-700 flex items-center gap-2">
                    <x-heroicon-o-envelope class="w-5 h-5 text-gray-500" />
                    <h3 class="font-medium">Ultimas Mensagens</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chat</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensagem</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Direcao</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @forelse($ultimasMensagens as $msg)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-sm">{{ $msg['chat_name'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $msg['message_text'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">{{ $msg['message_type'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($msg['is_from_me'])
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded flex items-center justify-center gap-1">
                                                <x-heroicon-s-arrow-up class="w-3 h-3" /> Enviada
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded flex items-center justify-center gap-1">
                                                <x-heroicon-s-arrow-down class="w-3 h-3" /> Recebida
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $msg['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Nenhuma mensagem
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Instancias WhatsApp --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-device-phone-mobile class="w-5 h-5 text-gray-500" />
                        <h3 class="font-medium">Instancias WhatsApp</h3>
                    </div>
                    <a href="{{ \App\Filament\Resources\WhatsappAccountResource::getUrl('index') }}" class="text-primary-600 hover:text-primary-700">
                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                    </a>
                </div>
                <div class="divide-y dark:divide-gray-700">
                    @forelse($instancias as $inst)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $inst['session_name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $inst['phone_number'] }}</div>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs {{ $inst['is_connected'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $inst['is_connected'] ? 'Online' : 'Offline' }}
                            </span>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500">
                            Nenhuma instancia
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
