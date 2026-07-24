<?php

namespace App\Services\ProductImport;

use App\Models\SourceIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class SourceIdentityRegistry
{
    /**
     * Get existing domain UUID or mint a new UUIDv7.
     *
     * @param  string  $entity  e.g. "chinook.artists"
     * @param  array<string, mixed>  $sourceKey  e.g. ["id" => "5"]
     */
    public function getOrMint(string $entity, array $sourceKey): string
    {
        $normalizedKey = $sourceKey;
        ksort($normalizedKey);

        $jsonKey = json_encode($normalizedKey);

        $record = SourceIdentity::where('entity', $entity)
            ->where('source_key', $jsonKey)
            ->first();

        if ($record !== null) {
            return $record->domain_id;
        }

        $domainId = (string) Str::uuid7();

        try {
            SourceIdentity::create([
                'entity' => $entity,
                'source_key' => $normalizedKey,
                'domain_id' => $domainId,
            ]);

            return $domainId;
        } catch (QueryException $e) {
            $record = SourceIdentity::where('entity', $entity)
                ->where('source_key', $jsonKey)
                ->first();

            if ($record !== null) {
                return $record->domain_id;
            }

            throw $e;
        }
    }
}
