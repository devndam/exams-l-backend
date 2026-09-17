<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SessionTerminated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $sessionId,
        public readonly int $candidateId,
        public readonly string $reason,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('session.'.$this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'session.terminated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'candidateId' => $this->candidateId,
            'reason' => $this->reason,
        ];
    }
}
