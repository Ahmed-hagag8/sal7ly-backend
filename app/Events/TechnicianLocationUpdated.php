<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public int $jobId;
    public float $latitude;
    public float $longitude;
    public ?float $heading;
    public ?float $speed;
    public string $updatedAt;
    public function __construct(int $jobId, float $latitude, float $longitude,
                                ?float $heading, ?float $speed, string $updatedAt)
    {
        $this->jobId = $jobId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->heading = $heading;
        $this->speed = $speed;
        $this->updatedAt = $updatedAt;
    }
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('job.' . $this->jobId);
    }
    public function broadcastAs(): string
    {
        return 'location.updated';
    }
}
