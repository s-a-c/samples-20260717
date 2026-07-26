<?php

declare(strict_types=1);

namespace App\Services\ProductReset;

use App\Enums\SamplesProduct;
use App\Exceptions\ProductResetWindowOpen;
use App\Models\ResetRun;

final class ResetWindow
{
    /** @var array<string, bool> */
    private array $memo = [];

    public function isOpen(SamplesProduct $product): bool
    {
        $key = $product->value;

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $open = ResetRun::where('product', $key)
            ->whereIn('status', ['pending', 'running', 'recovering'])
            ->exists();

        return $this->memo[$key] = $open;
    }

    public function assertWritable(SamplesProduct $product): void
    {
        if ($this->isOpen($product)) {
            throw new ProductResetWindowOpen("Reset window is currently open for product: {$product->value}");
        }
    }

    /**
     * Clear memoized state.
     */
    public function clearMemo(): void
    {
        $this->memo = [];
    }
}
