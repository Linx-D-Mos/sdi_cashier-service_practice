<?php

namespace App\Kafka\Handlers;

use App\Enum\CollectionStopStatus;
use App\Models\CollectionStop;
use App\Models\Store;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Message\ConsumedMessage;

class RouteStopCheckedOutHandler
{
    public function __invoke(ConsumedMessage $message): void
    {
        try {
            $body = $message->getBody();
            $payload = $body['payload'] ?? null;
            $attributes = $payload['attributes'] ?? null;

            if (($payload['type'] ?? '') !== 'route_stop.checked_out' || ! $attributes) {
                return;
            }

            $externalRouteStopId = $attributes['route_stop_id'];
            $externalStoreId = $attributes['store_id'];
            $collections = $payload['relationships']['route_stop_collections'] ?? [];

            DB::transaction(function () use ($externalRouteStopId, $externalStoreId, $collections, $payload) {

                // Bypass seguro de Mass Assignment para ID de Tiendas Proyectadas
                $store = Store::find($externalStoreId);
                if (! $store) {
                    $store = new Store();
                    $store->id = (int) $externalStoreId;
                    $store->name = $payload['relationships']['store']['name'] ?? "Tienda #{$externalStoreId}";
                    $store->save();
                }

                $stop = CollectionStop::query()->updateOrCreate(
                    ['external_route_stop_id' => $externalRouteStopId],
                    [
                        'store_id' => $externalStoreId,
                        'status' => CollectionStopStatus::DELIVERED,
                        'updated_at' => now(),
                        'checked_in_at' => Carbon::parse(now()),
                    ]
                );

                foreach ($collections as $bagData) {
                    // Ahora las llaves pasan limpias a través del $fillable corregido
                    $stop->collectedBags()->updateOrCreate(
                        ['external_collection_id' => $bagData['id']],
                        [
                            'bag_id' => $bagData['bag_id'],
                            'lock_id' => $bagData['lock_id'],
                            'packages_amount' => (int) $bagData['package_amount'],

                        ]
                    );
                }
            });

            Log::info("Kafka [route_stop.checked_out]: Procesadas con éxito " . count($collections) . " tulas para la parada {$externalRouteStopId}.");
        } catch (Exception $e) {
            Log::error('Kafka [route_stop.checked_out]: Error en procesamiento masivo 1:N.', [
                'error' => $e->getMessage()
            ]);

            if (app()->environment('testing')) {
                throw $e;
            }
        }
    }
}
