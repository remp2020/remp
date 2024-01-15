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

        $matcher = $this->exactly(2);
        $this->mockQueue->expects($matcher)->method('getTask')
            ->willReturnCallback(function (string $queueKey) use ($matcher, $message) {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals($queueKey, 'priority');
                        return null;
                    case 2:
                        $this->assertEquals($queueKey, 'default');
                        return [(new MessageSerializer)->serialize($message)];
                    default:
                        throw new \Exception('unhandled getTask() invocation');
                }
            });

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
        $messages = [
            1 => new Message('message_1', ['a' => 'b']),
            2 => new Message('message_2', ['a' => 'b']),
            3 => new Message('message_3', ['a' => 'b']),
        ];

        $matcher = $this->exactly(4);
        $this->mockQueue->expects($matcher)->method('getTask')
            ->willReturnCallback(function (string $queueKey) use ($matcher, $messages) {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals($queueKey, 'priority');
                        return [(new MessageSerializer)->serialize($messages[2])]; // 2 tasks in priority queue;
                    case 2:
                        $this->assertEquals($queueKey, 'priority');
                        return [(new MessageSerializer)->serialize($messages[3])]; // 1 tasks in priority queue
                    case 3:
                        $this->assertEquals($queueKey, 'priority');
                        return null; // 0 tasks in priority queue, moving on
                    case 4:
                        $this->assertEquals($queueKey, 'default');
                        return [(new MessageSerializer)->serialize($messages[1])]; // 1 task in default queue
                    default:
                        throw new \Exception('unhandled getTask() invocation');
                }
            });

        $processed = [];
        $this->driver->setMaxProcessItems(3);
        $this->driver->wait(function ($message) use (&$processed) {
            $processed[] = $message;
        });

        $this->assertCount(3, $processed);
        $this->assertEquals($messages[2]->getId(), $processed[0]->getId());
        $this->assertEquals($messages[3]->getId(), $processed[1]->getId());
        $this->assertEquals($messages[1]->getId(), $processed[2]->getId());
    }

    public function testNewPriorityTaskDuringRun(): void
    {
        $messages = [
            1 => new Message('message_1', ['a' => 'b']),
            2 => new Message('message_2', ['a' => 'b']),
            3 => new Message('message_3', ['a' => 'b']),
        ];

        $matcher = $this->exactly(6);
        $this->mockQueue->expects($matcher)->method('getTask')
            ->willReturnCallback(function (string $queueKey) use ($matcher, $messages) {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals($queueKey, 'priority');
                        return null; // 0 tasks in priority queue, moving on
                    case 2:
                        $this->assertEquals($queueKey, 'default');
                        return [(new MessageSerializer)->serialize($messages[1])]; // 1 task in default queue
                    case 3:
                        $this->assertEquals($queueKey, 'priority');
                        return [(new MessageSerializer)->serialize($messages[2])]; // 1 task in priority queue
                    case 4:
                        $this->assertEquals($queueKey, 'priority');
                        return null; // priority check again, 0 tasks in queue, moving on
                    case 5:
                        $this->assertEquals($queueKey, 'default');
                        return null; // 0 tasks in default queue, moving on
                    case 6:
                        $this->assertEquals($queueKey, 'priority');
                        return [(new MessageSerializer)->serialize($messages[3])]; // 1 task in default queue
                    default:
                        throw new \Exception('unhandled getTask() invocation');
                }
            });

        $processed = [];
        $this->driver->setMaxProcessItems(3);
        $this->driver->wait(function ($message) use (&$processed) {
            $processed[] = $message;
        });

        $this->assertCount(3, $processed);
        $this->assertEquals($messages[1]->getId(), $processed[0]->getId());
        $this->assertEquals($messages[2]->getId(), $processed[1]->getId());
        $this->assertEquals($messages[3]->getId(), $processed[2]->getId());
    }
}
