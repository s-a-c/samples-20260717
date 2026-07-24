<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;
use Tests\TestCase;

class UserUuidv7Test extends TestCase
{
    use RefreshDatabase;

    public function test_user_and_team_factories_use_valid_uuidv7_primary_keys(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->assertTrue(Str::isUuid($user->id));
        $this->assertTrue(Str::isUuid($team->id));

        $userUuid = Uuid::fromString($user->id);
        $teamUuid = Uuid::fromString($team->id);

        $this->assertInstanceOf(UuidV7::class, $userUuid);
        $this->assertInstanceOf(UuidV7::class, $teamUuid);
    }
}
