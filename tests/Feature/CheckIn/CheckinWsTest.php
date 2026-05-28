<?php

use App\Enum\CollectionStopStatus;
use App\Events\RouteStopArrived;
use App\Kafka\Handlers\RouteStopCheckedInHandler;
use Illuminate\Support\Facades\Event;
use Junges\Kafka\Message\ConsumedMessage;

describe('API V1 - CHECKIN WEBSOCKET TEST', function () {

    beforeEach(function () {
        Event::fake([RouteStopArrived::class]);
    });

    it('debe persistir el check-in y ordenar la transmisión por websocket inmediatamente', function () {
        // ARRANGE: Ajustamos el payload exacto que espera tu Handler ($body['payload']['attributes'])
        $mockPayload = [
            'payload' => [
                'type' => 'route_stop.checked_in',
                'attributes' => [
                    'route_stop_id' => 101,
                    'store_id' => 4,
                    'arrival_time' => '2026-05-28T14:20:00Z',
                    'emitted_at' => '2026-05-28T14:20:00Z'
                ],
                'relationships' => [
                    'store' => [
                        'name' => 'Tienda Centro #4'
                    ]
                ]
            ]
        ];

        // Instanciamos el objeto ConsumedMessage tal como lo exige la firma del Handler
        $message = new ConsumedMessage(
            topicName: 'sdi_security.route-stops.events',
            partition: 0,
            headers: [],
            body: $mockPayload,
            key: '4',
            offset: 1,
            timestamp: now()->timestamp
        );

        $handler = new RouteStopCheckedInHandler();

        // ACT
        $handler($message);

        // ASSERT: 1. Verificamos que la tienda cascarón se creó evadiendo el Mass Assignment
        $this->assertDatabaseHas('stores', [
            'id' => 4,
            'name' => 'Tienda Centro #4'
        ]);

        // ASSERT: 2. Apuntamos a la tabla e id reales de tu modelo CollectionStop
        $this->assertDatabaseHas('collection_stops', [
            'external_route_stop_id' => 101,
            'store_id' => 4,
            'status' => CollectionStopStatus::ARRIVING // Validamos contra tu Enum real
        ]);

        // ASSERT: 3. Validamos que el evento de Reverb se haya despachado con éxito
        Event::assertDispatched(RouteStopArrived::class, function ($event) {
            return (int) $event->storeId === 4
                && $event->status === 'IN_PROGRESS';
        });
    });
});
