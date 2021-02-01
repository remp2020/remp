<?php
declare(strict_types=1);

namespace Tests\Unit;

use Nette\DI\Container;
use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Hermes\HermesTasksQueue;
use Remp\MailerModule\Hermes\RedisDriver;
use Remp\MailerModule\Repositories\HermesTasksRepository;
use Tomaj\Hermes\Message;
use Tomaj\Hermes\MessageSerializer;

class RedisDriverTest extends TestCase
{
    private $driver;

    private $mockQueue;

    public function setUp(): void
    {
        /** @var Container $container */
        $container = $GLOBALS['container'];
        $this->mockQueue = $this->createMock(HermesTasksQueue::class);

        $this->driver = new RedisDriver(
            $container->getByType(HermesTasksRepository::class),
            $this->mockQueue
        );
        $this->driver->setupSleepTime(0);
        $this->driver->setupPriorityQueue('default', 100);
        $this->driver->setupPriorityQueue('priority', 200);
    }

    public function testSendSingleMessage(): void
    {
        $message = new Message('message_1', ['a' => 'b']);

        $this->mockQueue->method('getTask')
            ->withConsecutive(
                ['priority'],
                ['default']
            )
            ->willReturnOnConsecutiveCalls(
                null,
                [(new MessageSerializer)->serialize($message)]
            );

        $processed = [];

        $this->driver->setMaxProcessItems(1);
        $this->driver->wait(function ($message) use (&$processed) {
            $processed[] = $message;
        });

        $this->assertCount(1, $processed);
        $this->assertEquals($message->getId(), $processed[0]->getId());
    }

    public function testSendMultiplePriorityMessage(): void
    {
        $message1 = new Message('message_1', ['a' => 'b']);
        $message2 = new Message('message_2', ['a' => 'b']);
        $message3 = new Message('message_3', ['a' => 'b']);

        $this->mockQueue->method('getTask')
            ->withConsecutive(
                ['priority'],
                ['priority'],
                ['priority'],
                ['default']
            )
            ->willReturnOnConsecutiveCalls(
                [(new MessageSerializer)->serialize($message2)], // 2 tasks in priority queue
                [(new MessageSerializer)->serialize($message3)], // 1 tasks in priority queue
                null, // 0 tasks in priority queue, moving on
                [(new MessageSerializer)->serialize($message1)] // 1 task in default queue
            );

        $processed = [];
        $this->driver->setMaxProcessItems(3);
        $this->driver->wait(function ($message) use (&$processed) {
            $processed[] = $message;
        });

        $this->assertCount(3, $processed);
        $this->assertEquals($message2->getId(), $processed[0]->getId());
        $this->assertEquals($message3->getId(), $processed[1]->getId());
        $this->assertEquals($message1->getId(), $processed[2]->getId());
    }

    public function testNewPriorityTaskDuringRun(): void
    {
        $message1 = new Message('message_1', ['a' => 'b']);
        $message2 = new Message('message_2', ['a' => 'b']);
        $message3 = new Message('message_3', ['a' => 'b']);

        $this->mockQueue->method('getTask')
            ->withConsecutive(
                ['priority'],
                ['default'],
                ['priority'],
                ['priority'],
                ['default'],
                ['priority'],
            )
            ->willReturnOnConsecutiveCalls(
                null, // 0 tasks in priority queue, moving on
                [(new MessageSerializer)->serialize($message1)], // 1 task in default queue
                [(new MessageSerializer)->serialize($message2)], // 1 task in priority queue
                null, // priority check again, 0 tasks in queue, moving on
                null, // 0 tasks in default queue, moving on
                [(new MessageSerializer)->serialize($message3)], // 1 task in default queue
            );

        $processed = [];
        $this->driver->setMaxProcessItems(3);
        $this->driver->wait(function ($message) use (&$processed) {
            $processed[] = $message;
        });

        $this->assertCount(3, $processed);
        $this->assertEquals($message1->getId(), $processed[0]->getId());
        $this->assertEquals($message2->getId(), $processed[1]->getId());
        $this->assertEquals($message3->getId(), $processed[2]->getId());
    }
}
