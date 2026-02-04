<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'profile_image',
    ];
    protected $hidden = [
        'password',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    // ========== RELATIONSHIPS ==========
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
    public function technician()
    {
        return $this->hasOne(Technician::class);
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    // ========== HELPER METHODS ==========
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }
}
