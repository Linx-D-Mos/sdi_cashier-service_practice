<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RouteStopArrived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $storeId,
        public string $status,
        public string $arrivalTime
    )
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("store.{$this->storeId}"),
        ];
    }

    /**
     * name of the event in the frontend
     */
    public function broadcastAs(): string
    {
        return 'truck.approaching';
    }

    /**
     * Payload
     */
    public function broadcastWith(): array
    {
        return[
            'store_id' => $this->storeId,
            'status' => $this->status,
            'alert_message' => '¡Atención! El camión blindado ha ingresado al perímetro de la tienda.',
            'timestamp' => $this->arrivalTime,
        ];
    }
}
