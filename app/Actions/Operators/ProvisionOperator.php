<?php

declare(strict_types=1);

namespace App\Actions\Operators;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\Permission\Models\Role;

final class ProvisionOperator
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

            $team = $user->personalTeam();
            if ($team === null) {
                $team = app(CreateTeam::class)->handle($user, "{$name}'s Team", true);
            } elseif ($user->current_team_id === null) {
                $user->switchTeam($team);
            }

            $role = Role::findOrCreate('super_admin', 'web');
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            if (class_exists(ActivityLogger::class)) {
                app(ActivityLogger::class)
                    ->causedBy($user)
                    ->performedOn($user)
                    ->event('operator_created')
                    ->withProperties(['email' => $email])
                    ->log('System Operator provisioned');
            }

            $user->refresh();

            return $user;
        });
    }

    /**
     * Execute method signature for ProvisionOperator action.
     */
    public function execute(string $email, string $password, string $name = 'System Operator'): User
    {
        return $this->handle($name, $email, $password);
    }
}
