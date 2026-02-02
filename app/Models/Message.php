<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'is_read'];
    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
