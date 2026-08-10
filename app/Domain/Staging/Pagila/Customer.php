<?php

declare(strict_types=1);

namespace App\Domain\Staging\Pagila;

use App\Domain\Staging\StagingModel;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('pagila_staging.customers')]
final class Customer extends StagingModel {}
