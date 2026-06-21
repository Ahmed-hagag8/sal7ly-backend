<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateExceptUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:truncate-except-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all database tables except users, migrations, and personal_access_tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('Are you sure you want to truncate the database? This action is irreversible.')) {
            $this->info('Operation cancelled.');
            return;
        }

        // We must also keep customers, technicians, and wallets, because they are permanently tied to users.
        // If we delete them, the existing users will crash the application.
        $except = [
            'users', 
            'cities', 
            'service_categories', 
            'customers', 
            'technicians', 
            'wallets',
            'migrations', 
            'personal_access_tokens', 
            'password_reset_tokens'
        ];

        $tables = [];
        foreach (DB::select('SHOW TABLES') as $tableObj) {
            $tables[] = head(array_values((array) $tableObj));
        }

        Schema::disableForeignKeyConstraints();

        $this->withProgressBar($tables, function ($table) use ($except) {
            if (!in_array($table, $except)) {
                DB::table($table)->truncate();
            }
        });

        // Delete all users except ID 1, along with their core profile data
        DB::table('customers')->where('user_id', '!=', 1)->delete();
        DB::table('technicians')->where('user_id', '!=', 1)->delete();
        DB::table('wallets')->where('user_id', '!=', 1)->delete();
        DB::table('users')->where('id', '!=', 1)->delete();

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info('Database truncated successfully (Kept cities, categories, and User ID 1).');
    }
}
