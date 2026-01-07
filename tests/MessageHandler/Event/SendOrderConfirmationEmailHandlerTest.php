<?php

namespace App\Tests\MessageHandler\Event;

use App\Message\Command\SaveOrder;
use App\Message\Event\OrderSavedEvent;
use App\MessageHandler\Event\SendOrderConfirmationEmailHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Stopwatch\Stopwatch;

class SendOrderConfirmationEmailHandlerTest extends TestCase
{
    private SendOrderConfirmationEmailHandler $handler;
    private MockObject&MailerInterface $mailer;
    private MockObject&LoggerInterface $logger;
    private MockObject&Stopwatch $stopwatch;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);

        $this->handler = new SendOrderConfirmationEmailHandler(
            $this->mailer,
            $this->logger,
            $this->stopwatch
        );
    }

    #[Test]
    public function testHandleOrderSavedEventSendsEmail(): void
    {
        $orderId = 123;
        $saveOrder = new SaveOrder(
            userId: 1,
            stockSymbol: 'AAPL',
            quantity: 10,
            price: 150.50
        );
        $event = new OrderSavedEvent($orderId, $saveOrder);

        // Ожидаем логирование информации
        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->with($this->callback(function ($message) {
                return str_contains($message, 'заказа');
            }));

        // Ожидаем отправку email
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->handler->__invoke($event);
    }
}
