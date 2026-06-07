<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Plumbing',
                'name_ar' => 'سباكة',
                'icon' => null, // You can add icon path later
                'description' => 'Professional plumbing services including repairs, installations, and maintenance',
                'is_active' => 1,
            ],
            [
                'name' => 'Electrical',
                'name_ar' => 'كهرباء',
                'icon' => null,
                'description' => 'Electrical services including wiring, repairs, and installations',
                'is_active' => 1,
            ],
            [
                'name' => 'Carpentry',
                'name_ar' => 'نجارة',
                'icon' => null,
                'description' => 'Carpentry services including furniture repair, door installation, and woodwork',
                'is_active' => 1,
            ],
            [
                'name' => 'Painting',
                'name_ar' => 'نقاشة',
                'icon' => null,
                'description' => 'Painting and decoration services for homes and offices',
                'is_active' => 1,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('service_categories')->insert([
                'name' => $category['name'],
                'name_ar' => $category['name_ar'],
                'icon' => $category['icon'],
                'description' => $category['description'],
                'is_active' => $category['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}