<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['job_id', 'customer_id', 'technician_id', 'rating', 'comment', 'type'];

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
