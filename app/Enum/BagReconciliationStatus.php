<?php

namespace App\Enum;

enum BagReconciliationStatus: string
{
    case PENDING = 'pending';
    case MATCHED = 'matched';
    case DISCREPANCY = 'discrepancy';
    public function label(): string
    {
        return __("Enums/BagReconciliationStatus." . strtolower($this->value));
    }
}
