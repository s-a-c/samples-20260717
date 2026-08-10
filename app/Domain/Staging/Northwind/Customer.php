<?php

declare(strict_types=1);

namespace App\Domain\Staging\Northwind;

use App\Domain\Staging\StagingModel;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('northwind_staging.customers')]
final class Customer extends StagingModel {}
