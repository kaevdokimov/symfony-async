<?php

namespace App\Tests\MessageHandler\Command;

use App\Message\Command\SaveOrder;
use App\Message\Event\OrderSavedEvent;
use App\MessageHandler\Command\SaveOrderHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class SaveOrderHandlerTest extends TestCase
{
    private SaveOrderHandler $handler;
    private MockObject&MessageBusInterface $eventBus;
    private MockObject&LoggerInterface $logger;
    private MockObject&Stopwatch $stopwatch;

    protected function setUp(): void
    {
        $this->eventBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);

        $this->handler = new SaveOrderHandler(
            $this->eventBus,
            $this->logger,
            $this->stopwatch
        );
    }

    #[Test]
    public function testHandleValidSaveOrderCommand(): void
    {
        $command = new SaveOrder(
            userId: 1,
            stockSymbol: 'AAPL',
            quantity: 10,
            price: 150.50
        );

        // Command is valid, no exceptions expected

        // Expect logging
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Saving order', $this->callback(function ($context) use ($command) {
                return $context['userId'] === $command->userId
                    && $context['stockSymbol'] === $command->stockSymbol
                    && $context['quantity'] === $command->quantity
                    && $context['price'] === $command->price;
            }));

        // Expect event dispatch
        $this->eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) use ($command) {
                return $event instanceof OrderSavedEvent
                    && is_int($event->getOrderId())
                    && $event->getSaveOrder() === $command;
            }))
            ->willReturnCallback(function () {
                return new \Symfony\Component\Messenger\Envelope(new \stdClass());
            });

        $this->handler->__invoke($command);
    }

    #[Test]
    public function testHandleInvalidSaveOrderCommandThrowsException(): void
    {
        $command = new SaveOrder(
            userId: 1,
            stockSymbol: '', // Invalid: empty symbol
            quantity: 10,
            price: 150.50
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock symbol cannot be empty');

        $this->handler->__invoke($command);
    }

}
