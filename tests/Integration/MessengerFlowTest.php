<?php

namespace App\Tests\Integration;

use App\Message\Command\SaveOrder;
use App\Message\Event\OrderSavedEvent;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class MessengerFlowTest extends KernelTestCase
{
    private MessageBusInterface $commandBus;
    private MessageBusInterface $eventBus;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandBus = self::getContainer()->get('messenger.bus.command');
        $this->eventBus = self::getContainer()->get('messenger.bus.event');
    }

    #[Test]
    public function testCompleteOrderFlow(): void
    {
        // Create and dispatch SaveOrder command
        $command = new SaveOrder(
            userId: 1,
            stockSymbol: 'AAPL',
            quantity: 5,
            price: 200.00
        );

        // Dispatch command (this should trigger the event)
        $this->commandBus->dispatch($command);

        // Get async transport to check if events were queued
        /** @var InMemoryTransport $asyncTransport */
        $asyncTransport = self::getContainer()->get('messenger.transport.async');
        $envelopes = $asyncTransport->getSent();

        // Should have at least one event (OrderSavedEvent)
        $this->assertNotEmpty($envelopes, 'OrderSavedEvent should be dispatched');

        // Find OrderSavedEvent in the envelopes
        $orderSavedEvent = null;
        foreach ($envelopes as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof OrderSavedEvent) {
                $orderSavedEvent = $message;
                break;
            }
        }

        $this->assertNotNull($orderSavedEvent, 'OrderSavedEvent should be found in async transport');
        $this->assertInstanceOf(OrderSavedEvent::class, $orderSavedEvent);
        $this->assertIsInt($orderSavedEvent->getOrderId());
        $this->assertEquals($command, $orderSavedEvent->getSaveOrder());
    }

    #[Test]
    public function testEventBusDirectDispatch(): void
    {
        $command = new SaveOrder(
            userId: 2,
            stockSymbol: 'GOOGL',
            quantity: 3,
            price: 2500.00
        );

        $event = new OrderSavedEvent(999, $command);

        // Dispatch event directly to event bus
        $this->eventBus->dispatch($event);

        // Check that event was queued in async transport
        /** @var InMemoryTransport $asyncTransport */
        $asyncTransport = self::getContainer()->get('messenger.transport.async');
        $envelopes = $asyncTransport->getSent();

        // Find our event
        $found = false;
        foreach ($envelopes as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof OrderSavedEvent && $message->getOrderId() === 999) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'OrderSavedEvent should be queued in async transport');
    }

    #[Test]
    public function testCommandValidationInFlow(): void
    {
        // Test invalid command
        $invalidCommand = new SaveOrder(
            userId: -1, // Invalid: negative user ID
            stockSymbol: '', // Invalid: empty symbol
            quantity: 0, // Invalid: zero quantity
            price: -100.00 // Invalid: negative price
        );

        // This should throw an exception due to validation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order data');

        $this->commandBus->dispatch($invalidCommand);
    }
}
