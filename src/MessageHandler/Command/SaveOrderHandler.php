<?php

namespace App\MessageHandler\Command;

use App\Message\Command\SaveOrder;
use App\Message\Event\OrderSavedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsMessageHandler]
readonly class SaveOrderHandler
{
    public function __construct(
        private MessageBusInterface $eventBus,
        private LoggerInterface $logger,
        private Stopwatch $stopwatch,
    ) {
    }

    public function __invoke(SaveOrder $saveOrder): void
    {
        $this->stopwatch->start('save_order_handler');

        try {
            // Basic validation (without Symfony Validator for now)
            $this->validateBasicCommand($saveOrder);

            $this->logger->info('Saving order', [
                'userId' => $saveOrder->userId,
                'stockSymbol' => $saveOrder->stockSymbol,
                'quantity' => $saveOrder->quantity,
                'price' => $saveOrder->price,
            ]);

            // Save an order to database (mock implementation)
            $dbEvent = $this->stopwatch->start('database_save', 'save_order_handler');
            $orderId = random_int(1000, 9999);
            $dbEvent->stop();

            // Dispatch an event message on an event bus
            $dispatchEvent = $this->stopwatch->start('event_dispatch', 'save_order_handler');
            $this->eventBus->dispatch(new OrderSavedEvent($orderId, $saveOrder));
            $dispatchEvent->stop();

        } finally {
            $this->stopwatch->stop('save_order_handler');
        }
    }

    private function validateBasicCommand(SaveOrder $command): void
    {
        if ($command->userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }

        if (empty($command->stockSymbol)) {
            throw new \InvalidArgumentException('Stock symbol cannot be empty');
        }

        if ($command->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        if ($command->price <= 0) {
            throw new \InvalidArgumentException('Price must be positive');
        }
    }
}
