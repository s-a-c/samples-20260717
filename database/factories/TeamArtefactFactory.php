<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamArtefactType;
use App\Models\Team;
use App\Models\TeamArtefact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamArtefact>
 */
final class TeamArtefactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'type' => fake()->randomElement(TeamArtefactType::cases()),
            'configuration' => [],
            'created_by' => User::factory(),
        ];
    }
}
