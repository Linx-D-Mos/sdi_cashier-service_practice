<?php

namespace App\Kafka\Handlers;

use App\Enum\CollectionStopStatus;
use App\Events\RouteStopArrived;
use App\Models\CollectionStop;
use App\Models\Store; // <-- Aseguramos el uso del modelo Store local
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Message\ConsumedMessage;
use Exception;

class RouteStopCheckedInHandler
{
    public function __invoke(ConsumedMessage $message): void
    {
        try {
            $body = $message->getBody();
            $emittedAt = $body['emitted_at'] ?? null;
            $attributes = $body['payload']['attributes'] ?? null;

            if ( ($body['payload']['type']) !== 'route_stop.checked_in' || !$attributes) {
                Log::warning('Kafka [route_stop.checked_in]: Estructura de payload inválida.', ['body' => $body]);
                return;
            }
            Log::info("Kafka [{$body['payload']['type']}]: Iniciando procesamiento de evento.", ['type' => $body['payload']['type']]);
            $externalRouteStopId = $attributes['route_stop_id'] ?? null;
            $externalStoreId = $attributes['store_id'] ?? null;
            $arrivalTime = $attributes['arrival_time'] ?? null;

            if (! $externalRouteStopId || ! $externalStoreId) {
                Log::error('Kafka [route_stop.checked_in]: Faltan llaves de correlación críticas.');
                return;
            }

            $store = Store::find($externalStoreId);

            if (! $store) {
                $storeNameFallback = $body['payload']['relationships']['store']['name']
                    ?? "Tienda Externa ID {$externalStoreId}";

                $store = new Store();
                $store->id = (int) $externalStoreId;
                $store->name = $storeNameFallback;
                $store->save();

                Log::info("Kafka [route_stop.checked_in]: Tienda cascarón {$externalStoreId} creada dinámicamente.");
            }

            // 2. Persistencia Segura e Idempotente de la Parada
            CollectionStop::updateOrCreate(
                [
                    'external_route_stop_id' => $externalRouteStopId
                ],
                [
                    'store_id' => $externalStoreId,
                    'status' => CollectionStopStatus::ARRIVING,
                    'checked_in_at' => Carbon::parse($arrivalTime),
                ]
            );
            $timestampAlert = $emittedAt ?? $arrivalTime ?? now()->toIso8601String();

            RouteStopArrived::dispatch(
                $externalStoreId,
                'IN_PROGRESS',
                $timestampAlert
            );

            Log::info("Kafka [route_stop.checked_in]: Parada externa {$externalRouteStopId} procesada con éxito.");
        } catch (Exception $e) {
            // Capturamos cualquier fallo inesperado para evitar que el demonio de Kafka muera en producción
            Log::critical('Kafka [route_stop.checked_in]: Error catastrófico en el procesamiento del evento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
