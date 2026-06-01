<?php

namespace App\Kafka\Handlers;

use Illuminate\Support\Facades\Log;
use Junges\Kafka\Message\ConsumedMessage;

class RouteStopEventsDispatcher
{
    /**
     * Singe entry point for the route stop topic
     */
    public function __invoke(ConsumedMessage $message): void
    {
        $body = $message->getBody();

        $eventType = $body['payload']['type'] ?? null;

        if (! $eventType) {
            Log::warning('Kafka Dispatcher: Mensaje descargado por ausencia de tipo de evento.', [
                'topic' => $message->getTopicName(),
                'offset' => $message->getOffset()
            ]);
            return;
        }
        switch ($eventType) {
            case 'route_stop.checked_in':
                Log::info("Kafka Dispatcher: Enrutando a RouteStopCheckedInHandler [Offset: {$message->getOffset()}]");
                (new RouteStopCheckedInHandler())($message);
                break;

            case 'route_stop.checked_out':
                Log::info("Kafka Dispatcher: Enrutando a RouteStopCheckedOutHandler [Offset: {$message->getOffset()}]");
                (new RouteStopCheckedOutHandler())($message);
                break;

            default:
                Log::warning("Kafka Dispatcher: Evento de tipo [{$eventType}] no es de interés para el módulo de Cajas. Ignorando.");
                break;
        }
    }
}
