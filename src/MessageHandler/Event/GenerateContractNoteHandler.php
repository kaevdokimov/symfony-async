<?php

namespace App\MessageHandler\Event;

use App\Message\Event\OrderSavedEvent;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GenerateContractNoteHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private string $contractNotesPath = '/tmp/contract-notes',
    ) {
    }

    /**
     * @throws MpdfException
     */
    public function __invoke(OrderSavedEvent $event): void
    {
        $order = $event->getSaveOrder();
        $total = $order->quantity * $order->price;

        $this->logger->info('Генерация PDF контракта', [
            'orderId' => $event->getOrderId(),
            'userId' => $order->userId,
        ]);

        // Убедиться, что директория существует
        if (!is_dir($this->contractNotesPath)) {
            mkdir($this->contractNotesPath, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
        ]);
        $content = sprintf(
            '<h1>Контракт - Заказ №%d</h1>
            <p><strong>Символ акции:</strong> %s</p>
            <p><strong>Количество:</strong> %d</p>
            <p><strong>Цена за акцию:</strong> $%.2f</p>
            <p><strong>Общая сумма:</strong> $%.2f</p>
            <p><strong>Дата заказа:</strong> %s</p>',
            $event->getOrderId(),
            $order->stockSymbol,
            $order->quantity,
            $order->price,
            $total,
            date('Y-m-d H:i:s')
        );

        $mpdf->WriteHTML($content);

        $fileName = sprintf('contract-note-%d.pdf', $event->getOrderId());
        $filePath = $this->contractNotesPath . '/' . $fileName;

        $mpdf->Output($filePath, Destination::FILE);

        $this->logger->info('PDF контракт сгенерирован', [
            'orderId' => $event->getOrderId(),
            'filePath' => $filePath,
        ]);
    }
}
