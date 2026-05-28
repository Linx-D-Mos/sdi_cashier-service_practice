<?php

namespace App\Models;

use App\Enum\CollectionStopStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionStop extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionStopFactory> */
    use HasFactory, HasUuids;
    protected $fillable = [
        'id',
        'store_id',
        'external_route_stop_id',
        'status',
        'checked_in_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'delivered_at' => 'datetime',
            'status' => CollectionStopStatus::class,
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function collectedBags()
    {
        return $this->hasMany(CollectedBag::class);
    }

}
