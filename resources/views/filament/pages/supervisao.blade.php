<x-filament-panels::page>
    <div class="space-y-6" wire:poll.15s="loadData">
        {{-- Atendentes --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-gray-700">
                <h3 class="text-lg font-medium">Atendentes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Atendente</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500">Em Atendimento</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500">Finalizadas Hoje</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500">Ultimo Acesso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @forelse($atendentesStats as $atendente)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 font-medium">{{ $atendente['nome'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($atendente['status'] === 'online')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Online</span>
                                    @elseif($atendente['status'] === 'ausente')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Ausente</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Offline</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                        {{ $atendente['em_atendimento'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">{{ $atendente['finalizadas_hoje'] }}</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-500">
                                    {{ $atendente['ultimo_acesso'] ?? 'Nunca' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    Nenhum atendente cadastrado
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Conversas em Atendimento --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-gray-700">
                <h3 class="text-lg font-medium">Conversas em Atendimento ({{ count($conversasEmAtendimento) }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Cliente</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Atendente</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Instância</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Iniciada há</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @forelse($conversasEmAtendimento as $conversa)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $conversa['cliente_nome'] ?? 'Cliente' }}</div>
                                    <div class="text-sm text-gray-500">{{ $conversa['cliente_numero'] }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $conversa['atendente']['nome'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $conversa['account']['session_name'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $conversa['atendida_em'] ? \Carbon\Carbon::parse($conversa['atendida_em'])->diffForHumans() : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    Nenhuma conversa em atendimento
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
