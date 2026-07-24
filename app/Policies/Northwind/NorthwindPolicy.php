<?php

namespace App\Policies\Northwind;

use App\Models\Northwind\Northwind;
use App\Models\User;

class NorthwindPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('northwind_curator') || $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Northwind $northwind): bool
    {
        return $user->hasRole('northwind_curator') || $user->hasRole('super_admin');
    }
}
