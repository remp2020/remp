<?php

namespace Remp\MailerModule\Hermes;

use Closure;
use Remp\MailerModule\Repository\HermesTasksRepository;
use Tomaj\Hermes\Driver\DriverInterface;
use Tomaj\Hermes\MessageInterface;

class RedisDriver implements DriverInterface
{
    private $tasksRepository;

    private $tasksQueue;

    private $sleepTime = 5;

    public function __construct(HermesTasksRepository $tasksRepository, HermesTasksQueue $tasksQueue)
    {
        $this->tasksRepository = $tasksRepository;
        $this->tasksQueue = $tasksQueue;
    }

    public function send(MessageInterface $message)
    {
        $serializer = new HermesMessageSerializer();

        // update message data for hermes scheduling feature
        $task = $serializer->serialize($message);
        $message = $serializer->unserialize($task);

        $result = $this->tasksQueue->addTask($task, $message->getProcess());
        if ($result) {
            $this->tasksQueue->incrementType($message->getType());
        }

        return $result;
    }

    public function wait(Closure $callback)
    {
        $serializer = new HermesMessageSerializer();
        while (true) {
            $message = $this->tasksQueue->getTask();
            if ($message) {
                $hermesMessage = $serializer->unserialize($message[0]);
                $this->tasksQueue->decrementType($hermesMessage->getType());

                if ($hermesMessage->getProcess() > time()) {
                    $this->send($hermesMessage);
                    continue;
                }

                $result = $callback($hermesMessage);
                $this->tasksRepository->add(
                    $hermesMessage,
                    $result ? HermesTasksRepository::STATE_DONE : HermesTasksRepository::STATE_ERROR
                );
            } else {
                sleep($this->sleepTime);
            }
        }
    }
}
