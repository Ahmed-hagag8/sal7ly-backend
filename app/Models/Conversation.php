<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_request_id',
        'job_id',
        'participant_1_id',
        'participant_2_id',
        'last_message_at'
    ];
    protected $casts = [
        'last_message_at' => 'datetime',
    ];
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
    public function participant1()
    {
        return $this->belongsTo(User::class, 'participant_1_id');
    }
    public function participant2()
    {
        return $this->belongsTo(User::class, 'participant_2_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
