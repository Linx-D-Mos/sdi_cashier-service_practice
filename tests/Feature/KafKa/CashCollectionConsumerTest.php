<?php

use App\Enum\CollectionStopStatus;
use App\Kafka\Handlers\RouteStopCheckedInHandler;
use App\Models\CollectionStop;
use App\Models\Store;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Str;
use Junges\Kafka\Message\ConsumedMessage;

describe('API V1 - CASHIER SERVICE KAFKA CONSUMER TEST', function () {
    beforeEach(function () {
        Kafka::fake();
    });
    test('example', function () {
        $externalStoreId = 150;
        $externalRouteStopId = 4500;

        Store::factory()->create([
            'id' => $externalStoreId,
            'name' => 'Tienda centro financiero'
        ]);

        $eventId = Str::uuid()->toString();
        $arrivalTime = now()->toIso8601String();

        $messagePayload = [
            'event_id' => $eventId,
            'emitted_at' => now()->toIso8601String(),
            'payload' => [
                'type' => 'route_stop.checked_in',
                'attributes' => [
                    'route_stop_id' => $externalRouteStopId,
                    'store_id' => $externalStoreId,
                    'route_id' => 99,
                    'arrival_time' => $arrivalTime,
                ],
                'relationships' => [
                    'vehicle' => [
                        'id' => 1,
                        'plate' => 'GDF-456',
                    ],
                    'route_crews' => [
                        [
                            'id' => 10,
                            'name' => 'Juan Pérez (Guardia)',
                            'crew_person_role_id' => 2,
                            'crew_person_role_name' => 'Custodio',
                        ]
                    ],
                ]
            ]
        ];
        $consumedMessage = new ConsumedMessage(
            topicName: 'security.route-stops',
            partition: 0,
            headers: [],
            body: $messagePayload,
            key: null,
            offset: 1,
            timestamp: now()->getTimestamp()
        );

        // 2. ACT: Invocar directamente al Handler encargado de procesar este tópico
        $handler = new RouteStopCheckedInHandler();
        $handler($consumedMessage);

        // 3. ASSERT: Validar que los datos se persistieron de forma limpia y tipada en la base de datos de Caja
        $this->assertDatabaseHas('collection_stops', [
            'store_id' => $externalStoreId,
            'external_route_stop_id' => $externalRouteStopId,
            'status' => CollectionStopStatus::ARRIVING->value,
            'checked_in_at' => $arrivalTime
        ]);

        // Opcional: Verificamos mediante el modelo que el Enum se castea correctamente
        $collectionStop = CollectionStop::where('external_route_stop_id', $externalRouteStopId)->first();
        expect($collectionStop->status)->toBe(CollectionStopStatus::ARRIVING);
    });
});
