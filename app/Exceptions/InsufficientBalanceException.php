<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a wallet operation fails due to insufficient funds.
 *
 * Returns a 422 Unprocessable Entity instead of the generic 500 that
 * a plain \Exception would produce, so the client gets a proper error
 * response rather than an opaque server error.
 */
class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'Insufficient balance', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 422);
    }
}
