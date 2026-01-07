<?php

namespace App\MessageHandler\Event;

use App\Message\Event\OrderSavedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
readonly class SendOrderConfirmationEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(OrderSavedEvent $event): void
    {
        $order = $event->getSaveOrder();
        $total = $order->quantity * $order->price;

        $this->logger->info('Sending order confirmation email', [
            'orderId' => $event->getOrderId(),
            'userId' => $order->userId,
        ]);

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

        $this->mailer->send($email);

        $this->logger->info('Order confirmation email sent', [
            'orderId' => $event->getOrderId(),
        ]);
    }
}
