<?php

declare(strict_types=1);

namespace App\Domain\Staging\Chinook;

use App\Domain\Staging\StagingModel;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('chinook_staging.media_types')]
final class MediaType extends StagingModel {}
