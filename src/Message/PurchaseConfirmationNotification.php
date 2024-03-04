<?php

namespace App\Message;

readonly class PurchaseConfirmationNotification
{

        public function __construct(private int|string $orderId)
    {
    }

    public function getOrderId(): int|string
    {
        return $this->orderId;
    }

}
