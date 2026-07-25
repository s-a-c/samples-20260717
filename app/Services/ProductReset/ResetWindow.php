<?php

namespace App\Services\ProductReset;

use App\Exceptions\ProductResetWindowOpen;
use App\Models\ResetRun;

final class ResetWindow
{
    /** @var array<string, bool> */
    private array $memo = [];

    public function isOpen(string $product): bool
    {
        if (isset($this->memo[$product])) {
            return $this->memo[$product];
        }

        $open = ResetRun::where('product', $product)
            ->whereIn('status', ['pending', 'running', 'recovering'])
            ->exists();

        return $this->memo[$product] = $open;
    }

    public function assertWritable(string $product): void
    {
        if ($this->isOpen($product)) {
            throw new ProductResetWindowOpen("Reset window is currently open for product: {$product}");
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
