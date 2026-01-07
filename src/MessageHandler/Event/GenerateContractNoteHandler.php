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

        $this->logger->info('Generating contract note PDF', [
            'orderId' => $event->getOrderId(),
            'userId' => $order->userId,
        ]);

        // Ensure directory exists
        if (!is_dir($this->contractNotesPath)) {
            mkdir($this->contractNotesPath, 0755, true);
        }

        $mpdf = new Mpdf();
        $content = sprintf(
            '<h1>Contract Note - Order #%d</h1>
            <p><strong>Stock Symbol:</strong> %s</p>
            <p><strong>Quantity:</strong> %d</p>
            <p><strong>Price per share:</strong> $%.2f</p>
            <p><strong>Total Amount:</strong> $%.2f</p>
            <p><strong>Order Date:</strong> %s</p>',
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

        $this->logger->info('Contract note PDF generated', [
            'orderId' => $event->getOrderId(),
            'filePath' => $filePath,
        ]);
    }
}
