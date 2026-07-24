<?php

namespace App\Policies\Chinook;

use App\Models\Chinook\Chinook;
use App\Models\User;

class ChinookPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('chinook_curator') || $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Chinook $chinook): bool
    {
        return $user->hasRole('chinook_curator') || $user->hasRole('super_admin');
    }
}
