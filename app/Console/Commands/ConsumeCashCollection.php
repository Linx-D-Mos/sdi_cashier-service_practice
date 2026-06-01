<?php

namespace App\Console\Commands;

use App\Kafka\Handlers\RouteStopCheckedInHandler;
use App\Kafka\Handlers\RouteStopEventsDispatcher;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class ConsumeCashCollection extends Command
{
    protected $signature = 'kafka:consume-cash';
    protected $description = 'Enciende el demonio de escuchar para los eventos de conciliación de efectivo';

    public function handle(): int
    {
        $this->info('Servicio de cajas: conectando con apache Kafka...');

        $groupId = config('kafka.consumer_group_id', 'cashier-group');

        $topic = 'sdi_security-service.route-stops.events';
        $this->comment("Escuchando el tópico [{{$topic}}] bajo el grupo [{$groupId}]...");

        $consumer = Kafka::consumer([$topic])
        ->withConsumerGroupId($groupId)
        ->withHandler(new RouteStopEventsDispatcher())
        ->build();

        $consumer->consume();
        return Command::SUCCESS;
    }
}
