<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class FaceMonitoringEventOccurred implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $sessionId,
        public readonly int $candidateId,
        public readonly string $eventType,
        public readonly ?float $similarity,
        public readonly string $action,
        public readonly ?int $warningCount,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('session.'.$this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'monitoring.event';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'candidateId' => $this->candidateId,
            'eventType' => $this->eventType,
            'similarity' => $this->similarity,
            'action' => $this->action,
            'warningCount' => $this->warningCount,
        ];
    }
}
