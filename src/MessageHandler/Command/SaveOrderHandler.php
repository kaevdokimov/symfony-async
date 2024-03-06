<?php

namespace App\MessageHandler\Command;

use App\Message\Command\SaveOrder;
use App\Message\Event\OrderSavedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class SaveOrderHandler
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    public function __invoke(SaveOrder $saveOrder): void
    {
        // Save an order to database
        $orderId = 123;

        echo 'Order being saved' . PHP_EOL;

        // Dispatch an event message on an event bus
        $this->eventBus->dispatch(new OrderSavedEvent($orderId));
    }

}
