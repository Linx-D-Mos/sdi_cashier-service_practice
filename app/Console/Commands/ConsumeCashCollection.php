<?php

namespace App\Console\Commands;

use App\Kafka\Handlers\RouteStopCheckedInHandler;
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
        $this->comment("Escuchando el tópico [sdi_security-service.route-stops.events] bajo el grupo [{$groupId}]...");

        $consumer = Kafka::consumer(['sdi_security-service.route-stops.events'])
            ->withConsumerGroupId($groupId)
            ->withHandler(new RouteStopCheckedInHandler())
            ->build();
        $consumer->consume();

        return Command::SUCCESS;
    }
}
