<?php

namespace App\Policies;

use App\Models\Chinook;

class ChinookPolicy
{
    public function view(?Chinook $chinook): bool
    {
        return $chinook
            ? ($chinook->curator() || $chinook->owner() || $chinook->other_role())
            : false;
    }
}
