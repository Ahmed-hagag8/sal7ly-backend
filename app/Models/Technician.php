<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id',
        'service_category_id',
        'years_of_experience',
        'bio',
        'city_id',
        'latitude',
        'longitude',
        'verification_status',
        'verified_at',
        'verified_by',
        'average_rating',
        'total_jobs_completed',
        'is_available'
    ];
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'average_rating' => 'decimal:2',
        'is_available' => 'boolean',
        'verified_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function verifiedByAdmin()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
    public function jobOffers()
    {
        return $this->hasMany(JobOffer::class);
    }
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
    // Helper: Check if technician is approved
    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }
    public function documents()
    {
        return $this->hasMany(TechnicianDocument::class);
    }

}
