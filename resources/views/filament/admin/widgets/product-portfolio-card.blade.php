<x-filament-widgets::widget>
    @php
        $products = \App\Filament\Admin\Widgets\ProductPortfolioCard::getProducts();
        $product = $this->productKey !== null && isset($products[$this->productKey]) ? $products[$this->productKey] : null;
        $iconClass = 'w-8 h-8 text-primary-600 dark:text-primary-400';
    @endphp
    @if($product)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 overflow-hidden"
             wire:poll.60s="refreshStatus">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                        @if($product['key'] === 'chinook')
                            <x-heroicon-o-musical-note class="{{ $iconClass }}" />
                        @elseif($product['key'] === 'northwind')
                            <x-heroicon-o-truck class="{{ $iconClass }}" />
                        @elseif($product['key'] === 'pagila')
                            <x-heroicon-o-film class="{{ $iconClass }}" />
                        @endif
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $product['name'] }}
                    </h2>

                    @if($importStatus === 'running')
                        <span class="ml-auto inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                            <svg class="animate-spin -ml-1 mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Importing
                        </span>
                    @elseif($importStatus === 'succeeded')
                        <span class="ml-auto inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            <x-heroicon-m-check-circle class="w-3 h-3 mr-1" />
                            Succeeded
                        </span>
                    @elseif($importStatus === 'failed')
                        <span class="ml-auto inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            <x-heroicon-m-x-circle class="w-3 h-3 mr-1" />
                            Failed
                        </span>
                    @endif
                </div>

                <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                    {{ $product['description'] }}
                </p>

                <div class="grid grid-cols-3 gap-3 mb-6">
                    @foreach($stats as $index => $stat)
                        <div class="text-center p-2 rounded-lg bg-gray-50 dark:bg-gray-900/50 @if(in_array($index, $changedStats)) animate-pulse-green @endif">
                            <div class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $stat['value'] }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $stat['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($lastRefreshedAt)
                    <p class="text-xs text-gray-400 mb-4 text-center">
                        Last refreshed: {{ \Carbon\Carbon::parse($lastRefreshedAt)->diffForHumans() }}
                    </p>
                @endif

                <div class="flex gap-2">
                    <a
                        href="{{ $product['url'] }}"
                        class="inline-flex items-center justify-center flex-1 px-4 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-150"
                    >
                        Go to {{ $product['name'] }} Panel
                    </a>

                    <button
                        type="button"
                        wire:click="refreshStats"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-150 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        <svg wire:loading.remove wire:target="refreshStats" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                        </svg>
                        <svg wire:loading wire:target="refreshStats" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Refresh Stats
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">No product selected.</p>
        </div>
    @endif
</x-filament-widgets::widget>
