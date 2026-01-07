<?php

namespace App\MessageHandler\Event;

use App\Message\Event\OrderSavedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsMessageHandler]
readonly class SendOrderConfirmationEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private Stopwatch $stopwatch,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(OrderSavedEvent $event): void
    {
        $this->stopwatch->start('send_email_handler');

        try {
            $order = $event->getSaveOrder();
            $total = $order->quantity * $order->price;

            $this->logger->info('Sending order confirmation email', [
                'orderId' => $event->getOrderId(),
                'userId' => $order->userId,
            ]);

            // Email preparation
            $emailPrepEvent = $this->stopwatch->start('email_preparation', 'send_email_handler');
            $email = (new Email())
                ->from('sale@stocksapp.com')
                ->to('user@example.com') // In real app, get from user data
                ->subject('Order Confirmation - Order #' . $event->getOrderId())
                ->html(sprintf(
                    '<h1>Order Confirmation</h1><p>Your order #%d has been placed successfully.</p><p>Stock: %s</p><p>Quantity: %d</p><p>Price: $%.2f</p><p>Total: $%.2f</p>',
                    $event->getOrderId(),
                    $order->stockSymbol,
                    $order->quantity,
                    $order->price,
                    $total
                ));
            $emailPrepEvent->stop();

            // Email sending
            $emailSendEvent = $this->stopwatch->start('email_sending', 'send_email_handler');
            $this->mailer->send($email);
            $emailSendEvent->stop();

            $this->logger->info('Order confirmation email sent', [
                'orderId' => $event->getOrderId(),
            ]);

        } finally {
            $this->stopwatch->stop('send_email_handler');
        }
    }
}
