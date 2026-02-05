<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearServiceCache extends Command
{
    protected $signature = 'cache:services';
    protected $description = 'Clear service categories and services cache';

    public function handle()
    {
        Cache::forget('service_categories');
        Cache::forget('all_services');
        
        // Clear individual category caches
        $categories = \App\Models\ServiceCategory::pluck('id');
        foreach ($categories as $id) {
            Cache::forget("service_category_{$id}");
            Cache::forget("category_{$id}_services");
        }

        $this->info('Service cache cleared successfully!');
    }
}
