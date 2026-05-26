<?php

use App\Enum\BagReconciliationStatus;
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
        Schema::create('collected_bags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_stop_id')->constrained()->cascadeOnDelete()->comment('ID del punto de parada al que pertenece la bolsa');
            $table->unsignedBigInteger('external_collection_id')->unique()->index()->comment('ID de la bolsa en el sistema externo');
            $table->string('bag_identifier')->index()->comment('Identificador de la bolsa (puede ser código de barras, QR, etc.)');
            $table->string('lock_identifier')->comment('Identificador del candado asociado a la bolsa');
            $table->unsignedInteger('real_packages_amount')->nullable()->comment('Cantidad real de paquetes encontrados en la bolsa durante la recolección');
            $table->string('reconciliation_status')->default(BagReconciliationStatus::PENDING->value)->comment('Estado de la conciliación de la bolsa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collected_bags');
    }
};
