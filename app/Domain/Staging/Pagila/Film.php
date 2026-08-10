<?php

declare(strict_types=1);

namespace App\Domain\Staging\Pagila;

use App\Domain\Staging\StagingModel;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('pagila_staging.films')]
final class Film extends StagingModel {}
