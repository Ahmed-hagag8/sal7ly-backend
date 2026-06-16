<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'address',
        'city_id',
        'latitude',
        'longitude',
        'average_rating'
    ];
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'average_rating' => 'decimal:2',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    public function favoriteTechnicians()
    {
        return $this->belongsToMany(Technician::class, 'favorites');
    }
}
