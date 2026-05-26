<?php

use App\Enum\CollectionStopStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collection_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete()->comment('ID del punto de venta');
            $table->unsignedBigInteger('external_route_stop_id')->unique()->index()->comment('ID del punto de parada en el sistema externo');
            $table->string('status')->default(CollectionStopStatus::ARRIVING->value)->comment('Estado del punto de parada');
            $table->timestamp('checked_in_at')->comment('Fecha y hora de llegada al punto de parada');
            $table->timestamp('delivered_at')->nullable()->comment('Fecha y hora de entrega en el punto de parada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_stops');
    }
};
