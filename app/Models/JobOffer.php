<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_request_id',
        'technician_id',
        'offered_price',
        'estimated_duration',
        'notes',
        'status'
    ];
    protected $casts = [
        'offered_price' => 'decimal:2',
    ];
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
    public function job()
    {
        return $this->hasOne(Job::class);
    }
}
