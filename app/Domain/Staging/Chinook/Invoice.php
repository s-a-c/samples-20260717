<?php

declare(strict_types=1);

namespace App\Domain\Staging\Chinook;

use App\Domain\Staging\StagingModel;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('chinook_staging.invoices')]
final class Invoice extends StagingModel {}
