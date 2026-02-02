<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Get category IDs
        $plumbingId = DB::table('service_categories')->where('name', 'Plumbing')->first()->id;
        $electricalId = DB::table('service_categories')->where('name', 'Electrical')->first()->id;
        $carpentryId = DB::table('service_categories')->where('name', 'Carpentry')->first()->id;
        $paintingId = DB::table('service_categories')->where('name', 'Painting')->first()->id;

        $services = [
            // Plumbing Services
            [
                'category_id' => $plumbingId,
                'name' => 'Fix Water Leak',
                'name_ar' => 'إصلاح تسريب المياه',
                'description' => 'Repair water leaks in faucets, pipes, and fixtures',
                'base_price' => 150.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $plumbingId,
                'name' => 'Install Faucet',
                'name_ar' => 'تركيب صنبور',
                'description' => 'Install new faucets in kitchen or bathroom',
                'base_price' => 100.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $plumbingId,
                'name' => 'Repair Toilet',
                'name_ar' => 'إصلاح المرحاض',
                'description' => 'Fix toilet leaks, clogs, or mechanism issues',
                'base_price' => 200.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $plumbingId,
                'name' => 'Unclog Drain',
                'name_ar' => 'فتح البالوعة',
                'description' => 'Clear blocked drains and pipes',
                'base_price' => 120.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $plumbingId,
                'name' => 'Water Heater Repair',
                'name_ar' => 'إصلاح سخان المياه',
                'description' => 'Repair or replace water heater',
                'base_price' => 250.00,
                'is_active' => 1,
            ],

            // Electrical Services
            [
                'category_id' => $electricalId,
                'name' => 'Fix Electrical Wiring',
                'name_ar' => 'إصلاح الأسلاك الكهربائية',
                'description' => 'Repair faulty electrical wiring',
                'base_price' => 200.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $electricalId,
                'name' => 'Install Light Fixture',
                'name_ar' => 'تركيب وحدة إضاءة',
                'description' => 'Install ceiling lights, chandeliers, or wall lights',
                'base_price' => 150.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $electricalId,
                'name' => 'Repair Power Outlet',
                'name_ar' => 'إصلاح مقبس الكهرباء',
                'description' => 'Fix or replace electrical outlets',
                'base_price' => 80.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $electricalId,
                'name' => 'Install Circuit Breaker',
                'name_ar' => 'تركيب قاطع الدائرة',
                'description' => 'Install or replace circuit breakers',
                'base_price' => 300.00,
                'is_active' => 1,
            ],

            // Carpentry Services
            [
                'category_id' => $carpentryId,
                'name' => 'Fix Door',
                'name_ar' => 'إصلاح الباب',
                'description' => 'Repair broken doors or door frames',
                'base_price' => 180.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $carpentryId,
                'name' => 'Build Cabinet',
                'name_ar' => 'بناء خزانة',
                'description' => 'Build custom cabinets and storage',
                'base_price' => 500.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $carpentryId,
                'name' => 'Repair Furniture',
                'name_ar' => 'إصلاح الأثاث',
                'description' => 'Repair broken furniture',
                'base_price' => 150.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $carpentryId,
                'name' => 'Install Shelves',
                'name_ar' => 'تركيب الرفوف',
                'description' => 'Install wall shelves and storage units',
                'base_price' => 120.00,
                'is_active' => 1,
            ],

            // Painting Services
            [
                'category_id' => $paintingId,
                'name' => 'Paint Room',
                'name_ar' => 'طلاء الغرفة',
                'description' => 'Paint interior walls of a room',
                'base_price' => 400.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $paintingId,
                'name' => 'Paint Exterior',
                'name_ar' => 'طلاء خارجي',
                'description' => 'Paint building exterior walls',
                'base_price' => 800.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $paintingId,
                'name' => 'Wall Repair',
                'name_ar' => 'إصلاح الجدران',
                'description' => 'Repair cracks and holes in walls',
                'base_price' => 150.00,
                'is_active' => 1,
            ],
            [
                'category_id' => $paintingId,
                'name' => 'Wallpaper Installation',
                'name_ar' => 'تركيب ورق الحائط',
                'description' => 'Install decorative wallpaper',
                'base_price' => 300.00,
                'is_active' => 1,
            ],
        ];

        foreach ($services as $service) {
            DB::table('services')->insert([
                'service_category_id' => $service['category_id'],
                'name' => $service['name'],
                'description' => $service['description'],
                'is_active' => $service['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}