<?php

declare(strict_types=1);

namespace App\Services\ProductReset;

use App\Models\ResetConfirmation;
use App\Models\User;
use Illuminate\Support\Str;

final class ResetConfirmationService
{
    /**
     * Mint a new single-use reset confirmation token for an operator.
     */
    public function mint(User $operator, string $product, string $sha256, string $commit): string
    {
        $token = (string) Str::uuid7();

        ResetConfirmation::create([
            'operator_id' => $operator->id,
            'product' => $product,
            'source_sha256' => $sha256,
            'source_commit' => $commit,
            'token' => $token,
            'expires_at' => now()->addMinutes(15),
        ]);

        return $token;
    }

    /**
     * Atomically verify and consume a reset confirmation token.
     */
    public function verify(string $token): bool
    {
        $updated = ResetConfirmation::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }
}
