<?php

use App\Enum\BagReconciliationStatus;
use App\Enum\CollectionStopStatus;
use App\KafkaTopicEnum;
use App\Models\CollectedBag;
use App\Models\CollectionStop;
use App\Models\Store;
use App\Models\User;
use Junges\Kafka\Facades\Kafka;

describe('API V1 - RECONCILIATION TEST', function () {
    beforeEach(function () {
        Kafka::fake();

        $externalStoreId = 4;
        Store::factory()->create([
            'id' => $externalStoreId,
            'name' => 'Tienda centro financiero'
        ]);

        $this->supervisor = User::factory()->create([
            'store_id' => 4,
        ]);
        $this->stop = CollectionStop::query()->create([
            'store_id' => 4,
            'external_route_stop_id' => 99,
            'checked_in_at' => now()->toIso8601String(),
            'status' => CollectionStopStatus::DELIVERED,
        ]);
        $this->bag = CollectedBag::query()->create([
            'collection_stop_id' => $this->stop->id,
            'external_collection_id' => 777,
            'bag_id' => 'BAG-CONCI-01',
            'lock_id' => 'LOCK-CONCI-01',
            'packages_amount' => 3, // <-- El camión blindado declaró que aquí vienen 3 paquetes
            'reconciliation_status' => BagReconciliationStatus::PENDING,
        ]);
    });
    it('Must to mark the bag as matched if the summary with the supervisor is right', function () {
        $response = $this->actingAs($this->supervisor)->postJson("/api/v1/collected-bags/{$this->bag->id}/reconcile", [
            'counted_packages_amount' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.reconciliation_status', BagReconciliationStatus::MATCHED);
        $this->assertDatabaseHas('collected_bags', [
            'id' => $this->bag->id,
            'reconciliation_status' => BagReconciliationStatus::MATCHED
        ]);
        Kafka::assertNothingPublished();
    });
    it('must mark the bag as discrepancy and PUBLISH an alert to kafka if amounts mismatch', function () {
        // El supervisor cuenta 2 paquetes en vez de 3
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/collected-bags/{$this->bag->id}/reconcile", [
                'counted_packages_amount' => 2,
            ]);

        $response->assertStatus(200);

        // 🔍 ASERCIÓN CRÍTICA DE RETORNO: Verificamos que se publique la alerta en el tópico de regreso
        Kafka::assertPublishedOn(
            topic: KafkaTopicEnum::BAG_CONCILIATION_EVENT->value,
            callback: function ($message) {
                $body = $message->getBody();
                return $body['payload']['type'] === 'bag.reconciliation.discrepancy'
                    && (int) $body['payload']['attributes']['external_collection_id'] === 777
                    && (int) $body['payload']['attributes']['counted_packages_amount'] === 2;
            }
        );
    });
    it('Must to reject the operation with am 422 error code', function () {
        // ACT: Enviamos un string o un número negativo
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/collected-bags/{$this->bag->id}/reconcile", [
                'counted_packages_amount' => -5,
            ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['counted_packages_amount']);
    });
});
