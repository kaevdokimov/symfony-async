<?php

namespace App\Controller;

use App\Message\Command\SaveOrder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class StockTransactionController extends AbstractController
{
    #[Route('/buy', name: 'buy-stock', methods: ['GET'])]
    public function buy(MessageBusInterface $bus): Response
    {
        // Dispatch command to save order
        $command = new SaveOrder(
            userId: 1, // In real app, get from authenticated user
            stockSymbol: 'AAPL',
            quantity: 10,
            price: 150.50
        );

        $bus->dispatch($command);

        // Display confirmation to the user with caching headers
        return $this->render('stocks/example.html.twig', [], new Response('', 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]));
    }

}
