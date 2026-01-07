<?php

namespace App\Tests\MessageHandler\Event;

use App\Message\Command\SaveOrder;
use App\Message\Event\OrderSavedEvent;
use App\MessageHandler\Event\GenerateContractNoteHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GenerateContractNoteHandlerTest extends TestCase
{
    private GenerateContractNoteHandler $handler;
    private MockObject&LoggerInterface $logger;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tempDir = sys_get_temp_dir() . '/test-contract-notes-' . uniqid();

        $this->handler = new GenerateContractNoteHandler(
            $this->logger,
            $this->tempDir
        );
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function testHandleOrderSavedEventGeneratesPdf(): void
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
            ->with($this->logicalOr(
                $this->equalTo('Generating contract note PDF'),
                $this->equalTo('Contract note PDF generated')
            ))
            ->with($this->logicalOr(
                $this->equalTo(['orderId' => $orderId, 'userId' => 1]),
                $this->equalTo(['orderId' => $orderId, 'filePath' => $this->tempDir . '/contract-note-123.pdf'])
            ));

        $this->handler->__invoke($event);

        // Check that PDF file was created
        $expectedFile = $this->tempDir . '/contract-note-123.pdf';
        $this->assertFileExists($expectedFile);
        $this->assertGreaterThan(0, filesize($expectedFile));
    }

    #[Test]
    public function testCreatesDirectoryIfNotExists(): void
    {
        // Remove directory if it exists
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        $orderId = 456;
        $saveOrder = new SaveOrder(
            userId: 2,
            stockSymbol: 'GOOGL',
            quantity: 5,
            price: 2800.00
        );
        $event = new OrderSavedEvent($orderId, $saveOrder);

        $this->logger->expects($this->any())->method('info');

        $this->handler->__invoke($event);

        // Check that directory was created
        $this->assertDirectoryExists($this->tempDir);

        // Check that PDF file was created
        $expectedFile = $this->tempDir . '/contract-note-456.pdf';
        $this->assertFileExists($expectedFile);
    }
}
