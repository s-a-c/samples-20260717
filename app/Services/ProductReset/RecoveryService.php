<?php

namespace App\Services\ProductReset;

use App\Models\ResetRun;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RecoveryService
{
    /**
     * Create a recovery run for a failed reset run.
     *
     * @param  array<string, mixed>  $extraAttributes
     */
    public function createRecoveryRun(ResetRun $failedRun, array $extraAttributes = []): ResetRun
    {
        if ($failedRun->status !== 'failed') {
            throw new InvalidArgumentException("Cannot create recovery run for a reset run that is not failed (current status: {$failedRun->status}).");
        }

        $failedRun->update(['status' => 'recovering']);

        return ResetRun::create(array_merge([
            'id' => (string) Str::uuid7(),
            'product' => $failedRun->product,
            'kind' => 'recover',
            'status' => 'running',
            'current_phase' => 'initiating_recovery',
            'recovery_of' => $failedRun->id,
            'evidence' => [
                'parent_failed_run_id' => $failedRun->id,
            ],
        ], $extraAttributes));
    }

    /**
     * Check if a reset run can be recovered.
     */
    public function canRecover(ResetRun $run): bool
    {
        return $run->status === 'failed';
    }

    /**
     * Get the recovery run associated with a failed run.
     */
    public function getRecoveryRunFor(ResetRun $failedRun): ?ResetRun
    {
        return ResetRun::where('recovery_of', $failedRun->id)->first();
    }
}
