<?php

namespace App\Policies;

use App\Models\Northwind;

class NorthwindPolicy
{
    public function view(?Northwind $northwind): bool
    {
        return $northwind
            ? ($northwind->curator() || $northwind->owner() || $northwind->other_role())
            : false;
    }
}
