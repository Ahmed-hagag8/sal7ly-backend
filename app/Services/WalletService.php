<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\UniqueNumberGenerator;

class WalletService
{
    /**
     * Add funds to wallet (earnings)
     *
     * Uses DB::transaction + lockForUpdate to prevent the read-then-write
     * race condition where two concurrent credits could overwrite each other.
     */
    public function credit(Wallet $wallet, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId) {
            // Re-fetch with a pessimistic lock so no other process can read/write this row
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $transaction = Transaction::create([
                'transaction_number' => UniqueNumberGenerator::generate('TXN-', 'transactions', 'transaction_number', 10),
                'wallet_id' => $wallet->id,
                'type' => 'credit',
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
        });
    }

    /**
     * Deduct funds from wallet (withdrawal)
     *
     * Uses DB::transaction + lockForUpdate to prevent race conditions
     * and throws a renderable InsufficientBalanceException (422) instead
     * of a generic \Exception (500).
     */
    public function debit(Wallet $wallet, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId) {
            // Re-fetch with a pessimistic lock
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $transaction = Transaction::create([
                'transaction_number' => UniqueNumberGenerator::generate('TXN-', 'transactions', 'transaction_number', 10),
                'wallet_id' => $wallet->id,
                'type' => 'debit',
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
        });
    }

    /**
     * Hold funds in pending_balance when a withdrawal is requested.
     *
     * Moves money from available balance → pending_balance so the
     * technician cannot spend the same funds while the withdrawal
     * awaits admin approval. (SCALE-04)
     */
    public function holdFunds(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            $wallet->update([
                'balance' => $wallet->balance - $amount,
                'pending_balance' => $wallet->pending_balance + $amount,
            ]);
        });
    }

    /**
     * Release held funds back to available balance (withdrawal rejected).
     */
    public function releaseFunds(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            $wallet->update([
                'balance' => $wallet->balance + $amount,
                'pending_balance' => max(0, $wallet->pending_balance - $amount),
            ]);
        });
    }

    /**
     * Settle held funds on withdrawal approval.
     *
     * Reduces pending_balance and records the debit transaction
     * against the wallet's total_withdrawn counter.
     */
    public function settleHeldFunds(Wallet $wallet, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            $balanceBefore = $wallet->balance;

            $transaction = Transaction::create([
                'transaction_number' => UniqueNumberGenerator::generate('TXN-', 'transactions', 'transaction_number', 10),
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore, // balance doesn't change — it was already moved to pending
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_at' => now(),
            ]);

            $wallet->update([
                'pending_balance' => max(0, $wallet->pending_balance - $amount),
                'total_withdrawn' => $wallet->total_withdrawn + $amount,
            ]);

            return $transaction;
        });
    }
}
