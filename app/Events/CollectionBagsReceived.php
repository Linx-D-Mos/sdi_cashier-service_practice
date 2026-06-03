<?php

namespace App\Events;

use App\Enum\CollectionStopStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollectionBagsReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $storeId
     * @param array $bags List fo bags ingest from Kafka
     */
    public function __construct(
        public int $storeId,
        public array $bags
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("vault.{$this->storeId}"),
        ];
    }

    /**
     * Alias of the event for the javascript listener in the frontend.
     */
    public function broadcastAs(): string
    {
        return 'bags.incoming';
    }

    public function broadcastWith(): array
    {
        return[
            'store_id' => $this->storeId,
            'timestamp' => now()->toIso8601String(),
            'collections' => collect($this->bags)->map(fn($bag) => [
                'external_collection_id' => $bag['id'] ?? $bag['external_collection_id'],
                'bag_id' => $bag['bag_id'],
                'lock_id' => $bag['lock_id'],
                'packages_amount' => (int) $bag['packages_amount'],
                'status' => CollectionStopStatus::DELIVERED,
            ])->toArray(),
        ];
    }

}
