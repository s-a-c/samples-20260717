<?php

namespace App\Services\Search;

use Illuminate\Support\Str;

class SearchDeepLinkRegistry
{
    /**
     * Map (product, entityType, id) to Filament resource URL path.
     */
    public function getUrl(string $product, string $entityType, string $id): string
    {
        $pluralEntityType = Str::plural($entityType);

        return "/{$product}/{$pluralEntityType}/{$id}";
    }
}
