<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = ['service_category_id', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
