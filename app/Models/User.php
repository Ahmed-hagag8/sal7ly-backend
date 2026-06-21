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
    /**
     * SEC-13: 'role' and 'is_active' are intentionally excluded from $fillable
     * to prevent mass-assignment privilege escalation. They are set explicitly
     * in RegisterController and admin endpoints.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
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

    public function getProfileImageUrlAttribute(): ?string
    {
        if ($this->profile_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->profile_image)) {
            return asset('storage/' . $this->profile_image);
        }
        return null;
    }
}
