<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private City $city;
    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->city = City::create(['name' => 'Cairo', 'is_active' => true]);
        $this->category = ServiceCategory::create(['name' => 'Plumbing', 'is_active' => true]);
    }

    // ==================== CUSTOMER REGISTRATION ====================

    public function test_register_customer_success()
    {
        $response = $this->postJson('/api/register/customer', [
            'name' => 'Ahmed Customer',
            'phone' => '01111111111',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city_id' => $this->city->id,
            'address' => '123 Main St, Cairo',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer registered successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'email', 'phone', 'role'], 'token'],
            ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@example.com',
            'role' => 'customer',
        ]);

        // Verify customer profile was created
        $this->assertDatabaseHas('customers', [
            'city_id' => $this->city->id,
            'address' => '123 Main St, Cairo',
        ]);

        // Verify wallet was created
        $user = User::where('email', 'ahmed@example.com')->first();
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0,
        ]);
    }

    public function test_register_customer_missing_required_fields()
    {
        $response = $this->postJson('/api/register/customer', []);

        $response->assertStatus(422);
    }

    public function test_register_customer_duplicate_phone()
    {
        User::create([
            'name' => 'Existing',
            'phone' => '01111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/register/customer', [
            'name' => 'Duplicate',
            'phone' => '01111111111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city_id' => $this->city->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_register_customer_duplicate_email()
    {
        User::create([
            'name' => 'Existing',
            'email' => 'dup@example.com',
            'phone' => '01111111112',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/register/customer', [
            'name' => 'Duplicate',
            'email' => 'dup@example.com',
            'phone' => '01111111113',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city_id' => $this->city->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_register_customer_with_location()
    {
        $response = $this->postJson('/api/register/customer', [
            'name' => 'Located Customer',
            'phone' => '01111111114',
            'email' => 'located@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city_id' => $this->city->id,
            'address' => 'Near Pyramids',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'address' => 'Near Pyramids',
        ]);
    }

    // ==================== TECHNICIAN REGISTRATION ====================

    public function test_register_technician_success()
    {
        $response = $this->postJson('/api/register/technician', [
            'name' => 'Mohamed Tech',
            'phone' => '01222222222',
            'email' => 'tech@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city_id' => $this->city->id,
            'service_category_id' => $this->category->id,
            'years_of_experience' => 5,
            'bio' => 'Experienced plumber',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['verification_status' => 'pending'],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'email', 'phone', 'role'], 'verification_status', 'token'],
            ]);

        // Verify technician starts as pending
        $this->assertDatabaseHas('technicians', [
            'verification_status' => 'pending',
            'is_available' => false,
        ]);

        // Verify wallet was created
        $user = User::where('email', 'tech@example.com')->first();
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
        ]);
    }

    public function test_register_technician_missing_category()
    {
        $response = $this->postJson('/api/register/technician', [
            'name' => 'Bad Tech',
            'phone' => '01222222223',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city_id' => $this->city->id,
            // Missing service_category_id
        ]);

        $response->assertStatus(422);
    }

    public function test_register_technician_invalid_category()
    {
        $response = $this->postJson('/api/register/technician', [
            'name' => 'Bad Tech',
            'phone' => '01222222224',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city_id' => $this->city->id,
            'service_category_id' => 99999,
        ]);

        $response->assertStatus(422);
    }
}
