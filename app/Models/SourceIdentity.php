<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $entity
 * @property array<string, mixed> $source_key
 * @property string $domain_id
 * @property string $product
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['id', 'entity', 'source_key', 'domain_id'])]
final class SourceIdentity extends Model
{
    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_key' => 'array',
        ];
    }
}
