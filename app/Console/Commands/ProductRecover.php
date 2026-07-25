<?php

namespace App\Console\Commands;

use App\Models\ResetRun;
use App\Services\ProductReset\RecoveryService;
use Illuminate\Console\Command;

class ProductRecover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:recover {run_id : UUID of the failed reset run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initiate recovery for a failed product reset run';

    /**
     * Execute the console command.
     */
    public function handle(RecoveryService $recoveryService): int
    {
        /** @var string $runId */
        $runId = $this->argument('run_id');

        /** @var ResetRun|null $run */
        $run = ResetRun::find($runId);

        if ($run === null) {
            $this->error("Reset run '{$runId}' not found.");

            return self::FAILURE;
        }

        if (! $recoveryService->canRecover($run)) {
            $this->error("Reset run '{$runId}' cannot be recovered (current status: {$run->status}).");

            return self::FAILURE;
        }

        $recoveryRun = $recoveryService->createRecoveryRun($run);

        $this->info("Initiated recovery child run '{$recoveryRun->id}' for failed run '{$runId}'.");

        return self::SUCCESS;
    }
}
