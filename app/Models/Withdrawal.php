<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;
    protected $fillable = [
        'withdrawal_number',
        'user_id',
        'processed_by',
        'amount',
        'method',
        'status',
        'processed_at'
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
