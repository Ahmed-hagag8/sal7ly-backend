<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            // Cairo
            ['name' => 'Cairo', 'is_active' => 1],
            ['name' => 'Giza', 'is_active' => 1],
            ['name' => 'Helwan', 'is_active' => 1],
            ['name' => 'New Cairo', 'is_active' => 1],
            ['name' => '6th of October', 'is_active' => 1],
            
            // Alexandria
            ['name' => 'Alexandria', 'is_active' => 1],
            ['name' => 'Borg El Arab', 'is_active' => 1],
            
            // Major cities
            ['name' => 'Luxor', 'is_active' => 1],
            ['name' => 'Aswan', 'is_active' => 1],
            ['name' => 'Port Said', 'is_active' => 1],
            ['name' => 'Suez', 'is_active' => 1],
            ['name' => 'Mansoura', 'is_active' => 1],
            ['name' => 'Tanta', 'is_active' => 1],
            ['name' => 'Ismailia', 'is_active' => 1],
            ['name' => 'Fayoum', 'is_active' => 1],
            ['name' => 'Zagazig', 'is_active' => 1],
            ['name' => 'Assiut', 'is_active' => 1],
            ['name' => 'Damietta', 'is_active' => 1],
            ['name' => 'Hurghada', 'is_active' => 1],
            ['name' => 'Sharm El Sheikh', 'is_active' => 1],
        ];

        foreach ($cities as $city) {
            DB::table('cities')->insert([
                'name' => $city['name'],
                'is_active' => $city['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}