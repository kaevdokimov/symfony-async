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

        $this->logger->info('Отправка email с подтверждением заказа', [
            'orderId' => $event->getOrderId(),
            'userId' => $order->userId,
        ]);

        // Подготовка email
        $emailPrepEvent = $this->stopwatch->start('email_preparation', 'send_email_handler');
        $email = (new Email())
            ->from('sale@stocksapp.com')
            ->to('user@example.com') // В реальном приложении получить из данных пользователя
            ->subject('Подтверждение заказа - Заказ №' . $event->getOrderId())
            ->html(sprintf(
                '<h1>Подтверждение заказа</h1><p>Ваш заказ №%d успешно размещен.</p><p>Акция: %s</p><p>Количество: %d</p><p>Цена: $%.2f</p><p>Итого: $%.2f</p>',
                $event->getOrderId(),
                $order->stockSymbol,
                $order->quantity,
                $order->price,
                $total
            ));
        $emailPrepEvent->stop();

        // Отправка email
        $emailSendEvent = $this->stopwatch->start('email_sending', 'send_email_handler');
        $this->mailer->send($email);
        $emailSendEvent->stop();

        $this->logger->info('Email с подтверждением заказа отправлен', [
            'orderId' => $event->getOrderId(),
        ]);

        } finally {
            $this->stopwatch->stop('send_email_handler');
        }
    }
}
