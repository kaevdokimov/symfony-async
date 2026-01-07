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
        // Создать и отправить команду SaveOrder
        $command = new SaveOrder(
            userId: 1,
            stockSymbol: 'AAPL',
            quantity: 5,
            price: 200.00
        );

        // Отправить команду (это должно вызвать событие)
        $this->commandBus->dispatch($command);

        // Получить асинхронный транспорт для проверки постановки событий в очередь
        /** @var InMemoryTransport $asyncTransport */
        $asyncTransport = self::getContainer()->get('messenger.transport.async');
        $envelopes = $asyncTransport->getSent();

        // Должен быть хотя бы одно событие (OrderSavedEvent)
        $this->assertNotEmpty($envelopes, 'OrderSavedEvent should be dispatched');

        // Найти OrderSavedEvent в конвертах
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

        // Отправить событие напрямую в шину событий
        $this->eventBus->dispatch($event);

        // Проверить, что событие было поставлено в очередь асинхронного транспорта
        /** @var InMemoryTransport $asyncTransport */
        $asyncTransport = self::getContainer()->get('messenger.transport.async');
        $envelopes = $asyncTransport->getSent();

        // Найти наше событие
        $found = false;
        foreach ($envelopes as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof OrderSavedEvent && $message->getOrderId() === 999) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'OrderSavedEvent должен быть поставлен в очередь асинхронного транспорта');
    }

    #[Test]
    public function testCommandValidationInFlow(): void
    {
        // Тестирование невалидной команды
        $invalidCommand = new SaveOrder(
            userId: -1, // Неверно: отрицательный ID пользователя
            stockSymbol: '', // Неверно: пустой символ
            quantity: 0, // Неверно: нулевое количество
            price: -100.00 // Неверно: отрицательная цена
        );

        // Должно бросить исключение из-за валидации
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID пользователя должен быть положительным');

        $this->commandBus->dispatch($invalidCommand);
    }
}
