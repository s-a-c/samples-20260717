<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

covers(User::class);

test('user and team factories use valid uuidv7 primary keys', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this->assertTrue(Str::isUuid($user->id));
    $this->assertTrue(Str::isUuid($team->id));

    $userUuid = Uuid::fromString($user->id);
    $teamUuid = Uuid::fromString($team->id);

    $this->assertInstanceOf(UuidV7::class, $userUuid);
    $this->assertInstanceOf(UuidV7::class, $teamUuid);
});
