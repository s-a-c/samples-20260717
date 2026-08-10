<?php

declare(strict_types=1);

namespace App\Domain\Staging\Northwind;

use App\Domain\Staging\StagingModel;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('northwind_staging.products')]
final class Product extends StagingModel {}
