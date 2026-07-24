<?php

namespace App\Actions\Operators;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\Permission\Models\Role;

class ProvisionOperator
{
    /**
     * Provision the application operator and record the onboarding activity.
     */
    public function handle(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password): User {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $password],
            );

            if (! $user->wasRecentlyCreated && $user->name !== $name) {
                $user->update(['name' => $name]);
            }

            $team = $user->personalTeam() ?? Team::query()->create([
                'name' => "{$name}'s Team",
                'is_personal' => true,
            ]);

            $team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => TeamRole::Owner],
            );

            $user->switchTeam($team);
            $user->syncRoles(Role::findOrCreate('super_admin', 'web'));

            app(ActivityLogger::class)
                ->causedBy($user)
                ->performedOn($user)
                ->event('operator_created')
                ->withProperties(['email' => $email])
                ->log('System Operator provisioned');

            return $user->fresh();
        });
    }
}
