<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

final class ProductResetWindowOpen extends Exception
{
    public function __construct(
        string $message = 'Product reset window is currently open.',
        int $code = 423,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], 423);
        }

        return response(
            $this->getMessage(),
            423
        );
    }
}
