<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceRequest;
use Carbon\Carbon;

class ExpireOldRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending service requests older than 48 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = Carbon::now()->subHours(48);

        $expiredCount = ServiceRequest::where('status', 'pending')
            ->where('created_at', '<', $threshold)
            ->update(['status' => 'cancelled']);

        $this->info("Successfully expired/cancelled {$expiredCount} old pending requests.");
    }
}
