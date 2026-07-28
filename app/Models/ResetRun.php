<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $product
 * @property string $kind
 * @property string $status
 * @property string|null $current_phase
 * @property array|null $evidence
 * @property string|null $recovery_of
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ResetRun|null $recoveryOf
 * @property-read ResetRun|null $recoveryChild
 */
final class ResetRun extends Model
{
    use HasUuids;

    protected $table = 'reset_runs';

    protected $fillable = [
        'id',
        'product',
        'kind',
        'status',
        'current_phase',
        'evidence',
        'recovery_of',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evidence' => 'array',
        ];
    }

    /**
     * Get the parent reset run that this run is recovering.
     *
     * @return BelongsTo<ResetRun, $this>
     */
    public function recoveryOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recovery_of');
    }

    /**
     * Get the child recovery run for this reset run.
     *
     * @return HasOne<ResetRun, $this>
     */
    public function recoveryChild(): HasOne
    {
        return $this->hasOne(self::class, 'recovery_of');
    }
}
