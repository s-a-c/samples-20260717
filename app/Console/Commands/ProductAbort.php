<?php

namespace App\Console\Commands;

use App\Models\ResetRun;
use Illuminate\Console\Command;

class ProductAbort extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:abort {run_id : UUID of the active reset run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Abort an active product reset run';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $runId */
        $runId = $this->argument('run_id');

        /** @var ResetRun|null $run */
        $run = ResetRun::find($runId);

        if ($run === null) {
            $this->error("Reset run '{$runId}' not found.");

            return self::FAILURE;
        }

        if (! in_array($run->status, ['pending', 'running', 'recovering'], true)) {
            $this->error("Reset run '{$runId}' is not active (current status: {$run->status}).");

            return self::FAILURE;
        }

        $evidence = $run->evidence ?? [];
        $evidence['aborted_at'] = now()->toIso8601String();
        $evidence['aborted_by'] = 'cli';

        $run->update([
            'status' => 'failed',
            'current_phase' => 'aborted',
            'evidence' => $evidence,
        ]);

        $this->info("Reset run '{$runId}' has been aborted and marked as failed.");

        return self::SUCCESS;
    }
}
