<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a Stripe payment operation fails.
 *
 * Returns a 422 Unprocessable Entity with a user-friendly message,
 * following the same pattern as InsufficientBalanceException.
 */
class PaymentFailedException extends Exception
{
    public function __construct(string $message = 'Payment processing failed', int $code = 0, ?\Throwable $previous = null)
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
