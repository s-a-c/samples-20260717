@php
    $manifest = match ($product) {
        'chinook' => require database_path('sources/chinook.php'),
        'northwind' => require database_path('sources/northwind.php'),
        'pagila' => require database_path('sources/pagila.php'),
        default => null,
    };
@endphp
<div class="space-y-3">
    <div>
        <span class="font-medium text-gray-900 dark:text-white">Product:</span>
        <span class="text-gray-600 dark:text-gray-400 ml-2">{{ ucfirst($product ?? '') }}</span>
    </div>
    @if($manifest)
        <div>
            <span class="font-medium text-gray-900 dark:text-white">Source commit:</span>
            <code class="text-gray-600 dark:text-gray-400 ml-2 text-xs">{{ $manifest['commit_sha'] }}</code>
        </div>
    @endif

    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-3 text-sm text-yellow-800 dark:text-yellow-300">
        This operation will replace all live <strong>{{ ucfirst($product ?? '') }}</strong> data. The schematic swap is near-instantaneous, but the import pipeline may take several minutes. Data for other products is unaffected.
    </div>
</div>
