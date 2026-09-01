<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamArtefactType;
use Database\Factories\TeamArtefactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property string $id
 * @property string $team_id
 * @property TeamArtefactType $type
 * @property array<string, mixed> $configuration
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class TeamArtefact extends Model
{
    /** @use HasFactory<TeamArtefactFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    /**
     * @return array<string, class-string|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'type' => TeamArtefactType::class,
        ];
    }

    /**
     * The team that owns this artefact.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The user who created this artefact.
     *
     * Attribution survives the creator's team membership ending; if the user
     * record itself is deleted the foreign key nulls out but the artefact
     * remains (per CONTEXT.md's Team Artefact definition).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
