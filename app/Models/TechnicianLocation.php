<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianLocation extends Model
{
    protected $fillable = [
        'technician_id', 'job_id', 'latitude', 'longitude',
        'heading', 'speed', 'located_at',
    ];
    protected $casts = [
        'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
        'heading' => 'decimal:2', 'speed' => 'decimal:2',
        'located_at' => 'datetime',
    ];
    public function technician() { return $this->belongsTo(Technician::class); }
    public function job() { return $this->belongsTo(Job::class); }
}
