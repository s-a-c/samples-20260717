<x-filament-widgets::widget>
    @if($product)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                        @php
                            $iconClass = 'w-8 h-8 text-primary-600 dark:text-primary-400';
                        @endphp
                        @if($product['key'] === 'chinook')
                            <x-heroicon-o-musical-note class="{{ $iconClass }}" />
                        @elseif($product['key'] === 'northwind')
                            <x-heroicon-o-truck class="{{ $iconClass }}" />
                        @elseif($product['key'] === 'sakila')
                            <x-heroicon-o-film class="{{ $iconClass }}" />
                        @endif
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $product['name'] }}
                    </h2>
                </div>

                <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                    {{ $product['description'] }}
                </p>

                <div class="grid grid-cols-3 gap-3 mb-6">
                    @foreach($product['stats'] as $stat)
                        <div class="text-center p-2 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <div class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $stat['value'] }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $stat['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <a
                    href="{{ $product['url'] }}"
                    class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-150"
                >
                    Go to {{ $product['name'] }} Panel
                </a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">No product selected.</p>
        </div>
    @endif
</x-filament-widgets::widget>
