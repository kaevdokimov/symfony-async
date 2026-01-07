<?php

namespace App\Tests\Controller;

use App\Message\Command\SaveOrder;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class StockTransactionControllerTest extends WebTestCase
{
    #[Test]
    public function testBuyStocksReturnsSuccessfulResponse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/buy');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Thanks...we\'ve emailed you your contract note', $client->getResponse()->getContent());
    }

    #[Test]
    public function testBuyStocksDispatchesSaveOrderCommand(): void
    {
        $client = static::createClient();

        // Получить транспорт Messenger
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.sync');

        $client->request('GET', '/buy');

        $this->assertResponseIsSuccessful();

        // Проверить, что команда SaveOrder была отправлена
        $envelopes = $transport->getSent();
        $this->assertCount(1, $envelopes);

        $message = $envelopes[0]->getMessage();
        $this->assertInstanceOf(SaveOrder::class, $message);

        // Проверить свойства команды
        $this->assertEquals(1, $message->userId);
        $this->assertEquals('AAPL', $message->stockSymbol);
        $this->assertEquals(10, $message->quantity);
        $this->assertEquals(150.50, $message->price);
    }

    #[Test]
    public function testBuyStocksRouteExists(): void
    {
        $client = static::createClient();

        $client->request('GET', '/buy');

        $this->assertResponseStatusCodeSame(200);
    }

    #[Test]
    public function testInvalidRouteReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/invalid-route');

        $this->assertResponseStatusCodeSame(404);
    }
}
