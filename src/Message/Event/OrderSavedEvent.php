<?php

namespace App\Message\Event;

use App\Message\Command\SaveOrder;

readonly class OrderSavedEvent
{
    public function __construct(
        private int $orderId,
        private SaveOrder $saveOrder,
    ) {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getSaveOrder(): SaveOrder
    {
        return $this->saveOrder;
    }
}
