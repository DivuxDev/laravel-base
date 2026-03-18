<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user) {}

    /**
     * Broadcast on the public "notifications" channel so any
     * connected frontend client can listen without authentication.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('notifications'),
        ];
    }

    /**
     * Custom event name received by Laravel Echo on the frontend.
     */
    public function broadcastAs(): string
    {
        return 'user.logged-in';
    }

    /**
     * Payload sent to the frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'id'     => $this->user->id,
            'name'   => $this->user->name,
            'avatar' => $this->user->avatar,
        ];
    }
}
