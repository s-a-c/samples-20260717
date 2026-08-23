<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class PagilaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('pagila_curator') || $user->hasRole('super_admin');
    }

    public function view(User $user, mixed $pagila): bool
    {
        return $user->hasRole('pagila_curator') || $user->hasRole('super_admin');
    }
}
