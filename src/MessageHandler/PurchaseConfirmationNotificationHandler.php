<?php

namespace App\MessageHandler;

use App\Message\PurchaseConfirmationNotification;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
readonly class PurchaseConfirmationNotificationHandler
{

    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws MpdfException
     */
    public function __invoke(PurchaseConfirmationNotification $notification): void
    {
        // 1. Create a PDF contract note
        $mpdf = new Mpdf();
        $content = "<h1>Contract note for order {$notification->getOrderId()}</h1>";
        $content .= '<p>Total: <b>$1898.75</b></p>';
        $mpdf->WriteHTML($content);
        $contractNotePdf = $mpdf->Output('contract-note.pdf', Destination::STRING_RETURN);

            // 2. Email the contract note to the buyer

        $email = (new Email())
            ->from('sale@stocksapp.com')
            ->to('email@example.tech')
            ->subject('Contract note for order ' . $notification->getOrderId())
            ->text('Here is your contract note')
            ->attach($contractNotePdf, 'contract-note.pdf')
        ;

        $this->mailer->send($email);
    }

}
