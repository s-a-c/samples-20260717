<?php

namespace App\Models\Northwind;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Northwind extends Model
{
    use HasRoles;
}
