<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Closure;

/**
 * Manages the is_staging observer suppression flag.
 *
 * When active, Tier1SourceObserver skips EmbeddingJob dispatch.
 * The flag is scoped to staging writes and cleared in a finally path.
 */
class StagingContext
{
    private const string KEY = 'is_staging';

    /**
     * Activate staging mode: suppress observer side-effects.
     */
    public function activate(): void
    {
        app()->instance(self::KEY, true);
    }

    /**
     * Deactivate staging mode: restore observer side-effects.
     */
    public function deactivate(): void
    {
        app()->forgetInstance(self::KEY);
    }

    /**
     * Run a callback with staging mode active, then deactivate.
     */
    public function run(Closure $callback): mixed
    {
        $this->activate();

        try {
            return $callback();
        } finally {
            $this->deactivate();
        }
    }
}
