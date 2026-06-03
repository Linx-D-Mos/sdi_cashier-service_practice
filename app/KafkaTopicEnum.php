<?php

namespace App;

enum KafkaTopicEnum: string
{
    case BAG_CONCILIATION_EVENT = 'sdi_cashier.reconciliation.events';
}
