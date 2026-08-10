<?php

declare(strict_types=1);

namespace App\Domain\Staging\Pagila;

use App\Domain\Staging\StagingModel;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('pagila_staging.categories')]
final class Category extends StagingModel {}
