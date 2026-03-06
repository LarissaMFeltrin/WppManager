<x-filament-panels::page>
    <div class="space-y-6" wire:poll.10s="loadStats">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-yellow-500 rounded-lg">
                        <x-heroicon-o-clock class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['aguardando'] ?? 0 }}</div>
                        <div class="text-sm text-yellow-600 dark:text-yellow-400">Aguardando</div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-500 rounded-lg">
                        <x-heroicon-o-chat-bubble-left-right class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['em_atendimento'] ?? 0 }}</div>
                        <div class="text-sm text-blue-600 dark:text-blue-400">Em Atendimento</div>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-500 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $stats['finalizadas_hoje'] ?? 0 }}</div>
                        <div class="text-sm text-green-600 dark:text-green-400">Finalizadas Hoje</div>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-500 rounded-lg">
                        <x-heroicon-o-envelope class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $stats['mensagens_hoje'] ?? 0 }}</div>
                        <div class="text-sm text-purple-600 dark:text-purple-400">Mensagens Hoje</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Instâncias --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-lg font-medium mb-4">Instâncias WhatsApp</h3>
            <div class="flex items-center gap-2">
                <span class="text-3xl font-bold {{ ($stats['instancias_online'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $stats['instancias_online'] ?? 0 }}
                </span>
                <span class="text-gray-500">/ {{ $stats['instancias_total'] ?? 0 }} online</span>
            </div>
        </div>

        {{-- Atendentes Ativos --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-gray-700">
                <h3 class="text-lg font-medium">Atendentes Ativos</h3>
            </div>
            <div class="divide-y dark:divide-gray-700">
                @forelse($atendentes as $atendente)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                <x-heroicon-o-user class="w-4 h-4 text-primary-600" />
                            </div>
                            <span class="font-medium">{{ $atendente['nome'] }}</span>
                        </div>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                            {{ $atendente['conversas'] }} conversa(s)
                        </span>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500">
                        Nenhum atendente em atendimento
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
