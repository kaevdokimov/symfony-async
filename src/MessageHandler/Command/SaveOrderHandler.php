<?php

namespace App\MessageHandler\Command;

use App\Message\Command\SaveOrder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SaveOrderHandler
{
    public function __invoke(SaveOrder $saveOrder)
    {
        // Save an order to database
        $orderId = 123;

        echo 'Order being saved' . PHP_EOL;

        // Dispatch an event message on an event bus
    }

}
