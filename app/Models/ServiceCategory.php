<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'name_ar', 'icon', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }
    public function technicians()
    {
        return $this->hasMany(Technician::class);
    }
}
