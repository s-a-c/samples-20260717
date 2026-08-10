<?php

declare(strict_types=1);

namespace App\Domain\Staging;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for staging models.
 *
 * Staging models target `<product>_staging.<table>` and intentionally omit
 * {@see \App\Traits\BelongsToProductDomain}. This is the explicit import-only
 * exemption from the domain write guard: staging writes happen during a
 * running {@see \App\Models\ResetRun}, when live writes are blocked.
 *
 * The observer suppression flag (`is_staging`) is set by the mapper layer
 * to prevent EmbeddingJob dispatch during staging writes.
 */
abstract class StagingModel extends Model
{
    use HasUuids;

    protected $guarded = [];
}
