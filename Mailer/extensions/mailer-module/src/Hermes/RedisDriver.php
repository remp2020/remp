<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Closure;
use Remp\MailerModule\Repositories\HermesTasksRepository;
use Tomaj\Hermes\Driver\DriverInterface;
use Tomaj\Hermes\Driver\MaxItemsTrait;
use Tomaj\Hermes\Driver\ShutdownTrait;
use Tomaj\Hermes\Driver\UnknownPriorityException;
use Tomaj\Hermes\MessageInterface;
use Tomaj\Hermes\MessageSerializer;
use Tracy\Debugger;
use Tracy\ILogger;

class RedisDriver implements DriverInterface
{
    use ShutdownTrait;
    use MaxItemsTrait;

    public const PRIORITY_LOW = 50;
    public const PRIORITY_MEDIUM = 100; // default priority (see Tomaj\Hermes\Dispatcher::DEFAULT_PRIORITY)
    public const PRIORITY_HIGH = 200;

    private $tasksRepository;

    private $tasksQueue;

    private $serializer;

    private $sleepTime = 5;

    /** @var array<int, string>  */
    private $queues = [];
    
    private ?RedisDriverWaitCallbackInterface $redisDriverWaitCallback;

    public function __construct(
        HermesTasksRepository $tasksRepository,
        HermesTasksQueue $tasksQueue,
        ?RedisDriverWaitCallbackInterface $redisDriverWaitCallback = null
    ) {
        $this->tasksRepository = $tasksRepository;
        $this->tasksQueue = $tasksQueue;
        $this->serializer = new MessageSerializer();
        $this->redisDriverWaitCallback = $redisDriverWaitCallback;
    }

    public function setupPriorityQueue(string $name, int $priority): void
    {
        $this->queues[$priority] = $name;
    }

    public function setupSleepTime(int $sleepTime): void
    {
        $this->sleepTime = $sleepTime;
    }

    /**
     * @param int $priority
     * @return string
     *
     * @throws UnknownPriorityException
     */
    private function getKey(int $priority): string
    {
        if (!isset($this->queues[$priority])) {
            throw new UnknownPriorityException("Unknown priority {$priority}");
        }
        return $this->queues[$priority];
    }

    public function send(MessageInterface $message, int $priority = self::PRIORITY_MEDIUM): bool
    {
        $task = $this->serializer->serialize($message);
        $executeAt = 0;
        if ($message->getExecuteAt()) {
            $executeAt = $message->getExecuteAt();
        }

        $result = $this->tasksQueue->addTask($this->getKey($priority), $task, $executeAt);
        if ($result) {
            $this->tasksQueue->incrementType($message->getType());
        }

        return $result;
    }

    public function wait(Closure $callback, array $priorities = []): void
    {
        $queues = $this->queues;
        krsort($queues, SORT_NUMERIC);

        while (true) {
            $this->checkShutdown();
            if (!$this->shouldProcessNext()) {
                break;
            }
            $message = null;

            foreach ($queues as $priority => $name) {
                if (count($priorities) > 0 && !in_array($priority, $priorities, true)) {
                    continue;
                }

                $key = $this->getKey($priority);

                $message = $this->tasksQueue->getTask($key);
                if ($message === null) {
                    continue;
                }

                $hermesMessage = $this->serializer->unserialize($message[0]);
                $this->tasksQueue->decrementType($hermesMessage->getType());
                if ($hermesMessage->getExecuteAt() > time()) {
                    // This is probably not happening. Verifying with extra logs.
                    Debugger::log(
                        'RedisDriver received message with future execution time: ' . $hermesMessage->getExecuteAt(),
                        ILogger::WARNING
                    );
                    $this->send($hermesMessage, $priority);
                    break;
                }

                $result = $callback($hermesMessage);
                if (!$result) {
                    $this->tasksRepository->add(
                        $hermesMessage,
                        HermesTasksRepository::STATE_ERROR
                    );
                }
                $this->incrementProcessedItems();
                break;
            }
            
            if ($this->redisDriverWaitCallback) {
                $this->redisDriverWaitCallback->call();
            }

            if ($message === null) {
                sleep($this->sleepTime);
            }
        }
    }
}
