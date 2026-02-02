<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    protected $table = 'jobs';
    protected $fillable = [
        'job_number',
        'service_request_id',
        'job_offer_id',
        'customer_id',
        'technician_id',
        'agreed_price',
        'final_price',
        'status',
        'started_at',
        'completed_at'
    ];
    protected $casts = [
        'agreed_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
    public function offer()
    {
        return $this->belongsTo(JobOffer::class, 'job_offer_id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }
}
