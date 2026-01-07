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

        // Expect info logging
        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Sending order confirmation email', ['orderId' => $orderId, 'userId' => 1]],
                ['Order confirmation email sent', ['orderId' => $orderId]]
            );

        // Expect email sending
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($orderId, $saveOrder) {
                $this->assertEquals('sale@stocksapp.com', $email->getFrom()[0]->getAddress());
                $this->assertEquals('user@example.com', $email->getTo()[0]->getAddress());
                $this->assertStringContains('Order Confirmation - Order #' . $orderId, $email->getSubject());
                $this->assertStringContains('Order #' . $orderId, $email->getHtmlBody());
                $this->assertStringContains('AAPL', $email->getHtmlBody());
                $this->assertStringContains('10', $email->getHtmlBody());
                $this->assertStringContains('150.50', $email->getHtmlBody());
                $this->assertStringContains('1505.00', $email->getHtmlBody()); // 10 * 150.50
                return true;
            }));

        $this->handler->__invoke($event);
    }
}
