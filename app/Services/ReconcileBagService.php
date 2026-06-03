<?php

namespace App\Services;

use App\Enum\BagReconciliationStatus;
use App\KafkaTopicEnum;
use App\Models\CollectedBag;
use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class ReconcileBagService
{
    public function packagesAmountComparison(array $data, CollectedBag $collectedBag)
    {
        $counted = (int) $data['counted_packages_amount'];
        $expected = (int) $collectedBag->packages_amount;

        $newStatus = ($counted === $expected)
            ? BagReconciliationStatus::MATCHED
            : BagReconciliationStatus::DISCREPANCY;

        $collectedBag->update([
            'reconciliation_status' => $newStatus,
        ]);

        if ($newStatus === BagReconciliationStatus::DISCREPANCY) {
            $this->discrepancyAlert($collectedBag, $expected, $counted);
        }
        return response()->json([
            'message' => 'Tula conciliada de manera exitosa.',
            'data' => [
                'id' => $collectedBag->id,
                'bag_id' => $collectedBag->bag_id,
                'reconciliation_status' => $newStatus,
            ]
        ], 200);
    }
    public function discrepancyAlert(CollectedBag $collectedBag, int $expected, int $counted)
    {
        $payloadMessage = [
            'event_id' => Str::uuid()->toString(),
            'emitted_at' => now()->toIso8601String(),
            'payload' => [
                'type' => 'bag.reconciliation.discrepancy',
                'attributes' => [
                    'external_collection_id' => (int) $collectedBag->external_collection_id,
                    'bag_id' => $collectedBag->bag_id,
                    'lock_id' => $collectedBag->lock_id,
                    'expected_packages_amount' => $expected,
                    'counted_packages_amount' => $counted,
                    'audited_at' => now()->toIso8601String(),
                ]
            ]
        ];
        $message = new Message(
            body: $payloadMessage,
            key: (string) $collectedBag->collectionStop->store_id
        );

        Kafka::publish()
            ->onTopic(KafkaTopicEnum::BAG_CONCILIATION_EVENT->value)
            ->withMessage($message)
            ->send();
    }
}
