<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            ServiceCategorySeeder::class,
            AdminSeeder::class,
            TestUsersSeeder::class,
        ]);

        echo "\n✅ Database seeding completed successfully!\n";
    }
}