<?php

namespace Remp\MailerModule\Hermes;

use Closure;
use Remp\MailerModule\Repository\HermesTasksRepository;
use Tomaj\Hermes\Driver\DriverInterface;
use Tomaj\Hermes\MessageInterface;
use Tomaj\Hermes\MessageSerializer;

class RedisDriver implements DriverInterface
{
    private $tasksRepository;

    private $tasksQueue;

    private $serializer;

    private $sleepTime = 5;

    public function __construct(HermesTasksRepository $tasksRepository, HermesTasksQueue $tasksQueue)
    {
        $this->tasksRepository = $tasksRepository;
        $this->tasksQueue = $tasksQueue;
        $this->serializer = new MessageSerializer();
    }

    public function send(MessageInterface $message): bool
    {
        $task = $this->serializer->serialize($message);
        $executeAt = 0;
        if ($message->getExecuteAt()) {
            $executeAt = $message->getExecuteAt();
        }

        $result = $this->tasksQueue->addTask($task, $executeAt);
        if ($result) {
            $this->tasksQueue->incrementType($message->getType());
        }

        return $result;
    }

    public function wait(Closure $callback): void
    {
        while (true) {
            $message = $this->tasksQueue->getTask();
            if ($message) {
                $hermesMessage = $this->serializer->unserialize($message[0]);
                $this->tasksQueue->decrementType($hermesMessage->getType());
                if ($hermesMessage->getExecuteAt() > time()) {
                    $this->send($hermesMessage);
                    continue;
                }

                $result = $callback($hermesMessage);
                if (!$result) {
                    $this->tasksRepository->add(
                        $hermesMessage,
                        HermesTasksRepository::STATE_ERROR
                    );
                }
            } else {
                sleep($this->sleepTime);
            }
        }
    }
}
