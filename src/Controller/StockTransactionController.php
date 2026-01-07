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
        // Отправка команды для сохранения заказа
        $command = new SaveOrder(
            userId: 1, // В реальном приложении получить от аутентифицированного пользователя
            stockSymbol: 'AAPL',
            quantity: 10,
            price: 150.50
        );

        $bus->dispatch($command);

        // Отображение подтверждения пользователю с заголовками кеширования
        return $this->render('stocks/example.html.twig', [], new Response('', 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]));
    }

}
