<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ResetRun;
use Illuminate\Console\Command;

final class ProductStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:status {product? : chinook|northwind|pagila}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display active and recent product reset runs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $product = $this->argument('product') !== null ? mb_strtolower($this->argument('product')) : null;

        $query = ResetRun::query();

        if ($product !== null) {
            $query->where('product', $product);
        }

        $runs = $query->orderBy('created_at', 'desc')->take(20)->get();

        if ($runs->isEmpty()) {
            $this->info('No reset runs found.');

            return self::SUCCESS;
        }

        $rows = $runs->map(fn (ResetRun $run) => [
            $run->id,
            $run->product,
            $run->kind,
            $run->status,
            $run->current_phase ?? '-',
            $run->created_at?->toDateTimeString() ?? '-',
        ])->toArray();

        $this->table(['ID', 'Product', 'Kind', 'Status', 'Phase', 'Created At'], $rows);

        return self::SUCCESS;
    }
}
