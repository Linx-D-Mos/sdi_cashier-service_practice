<?php

use App\Enum\BagReconciliationStatus;
use App\Kafka\Handlers\RouteStopCheckedOutHandler;
use App\Enum\CollectionStopStatus;
use App\Events\CollectionBagsReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Junges\Kafka\Message\ConsumedMessage;

beforeEach(function () {
    // 🔍 Falkeamos el evento para capturarlo en la capa de transporte
    Event::fake([CollectionBagsReceived::class]);
});
it('debe procesar el check-out e ingresar de forma atomica el listado 1:N de tulas', function () {
    // ARRANGE: Payload exacto generado por tu RouteStopCheckedOutMessage del productor

    $mockPayload = [
        'event_id' => 'fe90c345-4221-4d39-a681-7928cb521990',
        'emitted_at' => '2026-05-29T15:00:00Z',
        'payload' => [
            'type' => 'route_stop.checked_out',
            'attributes' => [
                'store_id' => 4,
                'route_stop_id' => 45,
                'route_id' => 12,
                'departure_time' => '2026-05-29T14:55:00Z',
            ],
            'relationships' => [
                'store' => [
                    'id' => 4,
                    'name' => 'Tienda Centro #4'
                ],
                'route_stop_collections' => [
                    [
                        'id' => 501,
                        'bag_id' => 'BAG-A1',
                        'lock_id' => 'LOCK-X1',
                        'package_amount' => 3
                    ],
                    [
                        'id' => 502,
                        'bag_id' => 'BAG-A2',
                        'lock_id' => 'LOCK-X2',
                        'package_amount' => 1
                    ]
                ]
            ]
        ]
    ];

    $message = new ConsumedMessage(
        topicName: 'sdi_security.route-stops.events',
        partition: 0,
        headers: [],
        body: $mockPayload,
        key: '4',
        offset: 2,
        timestamp: now()->timestamp
    );

    $handler = new RouteStopCheckedOutHandler;

    // ACT
   // ACT
    $handler($message);

    // ASSERT: 1. Comprobamos la actualización del estado de la parada madre
    $this->assertDatabaseHas('collection_stops', [
        'external_route_stop_id' => 45,
        'status' => CollectionStopStatus::DELIVERED
    ]);

    // ASSERT: 2. Comprobamos la inserción atómica mapeando contra la migración real
    $this->assertDatabaseHas('collected_bags', [
        'external_collection_id' => 501,
        'bag_id' => 'BAG-A1',
        'packages_amount' => 3,
        'reconciliation_status' => BagReconciliationStatus::PENDING->value
    ]);

    $this->assertDatabaseHas('collected_bags', [
        'external_collection_id' => 502,
        'bag_id' => 'BAG-A2',
        'packages_amount' => 1,
        'reconciliation_status' => BagReconciliationStatus::PENDING->value
    ]);
    Event::assertDispatched(CollectionBagsReceived::class, function ($event) {
        // Validamos quirúrgicamente que el evento vaya con el Store correcto y la data 1:N completa
        return (int) $event->storeId === 4
            && count($event->bags) === 2
            && $event->bags[0]['bag_id'] === 'BAG-A1'
            && (int) $event->bags[0]['package_amount'] === 3;
    });
});
