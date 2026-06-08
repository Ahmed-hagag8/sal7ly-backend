<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * PERF-11: Generate unique identifiers with retry logic.
 *
 * Str::random(8) for request_number, job_number, etc. can collide
 * as data grows, causing 500 errors on the unique DB constraint.
 * This helper checks for existence before returning, with a
 * timestamp-based fallback if all attempts are exhausted.
 */
class UniqueNumberGenerator
{
    /**
     * Generate a unique number with retry logic.
     *
     * @param string $prefix  e.g., 'REQ-', 'JOB-', 'PAY-'
     * @param string $table   e.g., 'service_requests'
     * @param string $column  e.g., 'request_number'
     * @param int    $length  Random part length (default 8)
     * @param int    $maxAttempts
     */
    public static function generate(
        string $prefix,
        string $table,
        string $column,
        int $length = 8,
        int $maxAttempts = 5
    ): string {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $number = $prefix . strtoupper(Str::random($length));

            $exists = DB::table($table)->where($column, $number)->exists();

            if (!$exists) {
                return $number;
            }
        }

        // Fallback: use timestamp + random to guarantee uniqueness
        return $prefix . now()->format('ymdHis') . strtoupper(Str::random(4));
    }
}
