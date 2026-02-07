<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Add funds to wallet (earnings)
     */
    public function credit(Wallet $wallet, float $amount, string $type, string $description, ?string $referenceType = null, ?int $referenceId = null): Transaction
    {
        $balanceBefore = $wallet->balance;
        $balanceAfter = $balanceBefore + $amount;

        $transaction = Transaction::create([
            'transaction_number' => 'TXN-' . strtoupper(Str::random(10)),
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'created_at' => now(),
        ]);

        $wallet->update([
            'balance' => $balanceAfter,
            'total_earned' => $wallet->total_earned + $amount,
        ]);

        return $transaction;
    }

    /**
     * Deduct funds from wallet (withdrawal)
     */
    public function debit(Wallet $wallet, float $amount, string $type, string $description, ?string $referenceType = null, ?int $referenceId = null): Transaction
    {
        if ($wallet->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $balanceBefore = $wallet->balance;
        $balanceAfter = $balanceBefore - $amount;

        $transaction = Transaction::create([
            'transaction_number' => 'TXN-' . strtoupper(Str::random(10)),
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'created_at' => now(),
        ]);

        $wallet->update([
            'balance' => $balanceAfter,
            'total_withdrawn' => $wallet->total_withdrawn + $amount,
        ]);

        return $transaction;
    }
}
