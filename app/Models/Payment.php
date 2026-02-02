<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'payment_number',
        'job_id',
        'customer_id',
        'technician_id',
        'amount',
        'commission_amount',
        'technician_earnings',
        'payment_method',
        'status',
        'paid_at'
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'technician_earnings' => 'decimal:2',
        'paid_at' => 'datetime',
    ];
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
