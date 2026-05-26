<?php

namespace App\Enum;

enum CollectionStopStatus: string
{
    case ARRIVING = 'arriving';
    case IN_PROGESS = 'in_progress';
    case DELIVERED = 'delivered';
    case RECONCILIED = 'reconciled';
    public function label(): string
    {
        return __("Enums/CollectionStopStatus.". strtolower($this->value));
    }
}
