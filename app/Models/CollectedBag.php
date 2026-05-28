<?php

namespace App\Models;

use App\Enum\BagReconciliationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectedBag extends Model
{
    /** @use HasFactory<\Database\Factories\CollectedBagFactory> */
    use HasFactory, HasUuids;
    protected $fillable = [
        'id',
        'collection_stop_id',
        'external_collection_id',
        'bag_identifier',
        'lock_identifier',
        'real_packages_amount',
        'reconciliation_status',
    ];

    protected function casts(): array
    {
        return [
            'reconciliation_status' => BagReconciliationStatus::class,
            'real_packages_amount' => 'integer',
        ];
    }

    public function collectionStop()
    {
        return $this->belongsTo(CollectionStop::class);
    }

}
