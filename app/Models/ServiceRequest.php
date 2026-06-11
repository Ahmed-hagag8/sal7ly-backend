<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'request_number',
        'customer_id',
        'category_id',
        'title',
        'description',
        'address',
        'city_id',
        'latitude',
        'longitude',
        'preferred_date',
        'preferred_time',
        'status',
        'ai_predicted_price'
    ];
    protected $casts = [
        'preferred_date' => 'date',
        'ai_predicted_price' => 'decimal:2',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function images()
    {
        return $this->hasMany(ServiceImage::class);
    }
    public function offers()
    {
        return $this->hasMany(JobOffer::class);
    }
    public function job()
    {
        return $this->hasOne(Job::class);
    }
}
