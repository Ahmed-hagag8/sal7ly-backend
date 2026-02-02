<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_request_id',
        'path',
        'status',
        'ai_checked_at',
        'ai_result',
        'rejection_reason'
    ];
    protected $casts = [
        'ai_checked_at' => 'datetime',
    ];
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
