<?php

namespace Remp\MailerModule\Hermes;

use Closure;
use Tomaj\Hermes\Dispatcher;
use Tomaj\Hermes\Driver\DriverInterface;
use Tomaj\Hermes\Driver\NotSupportedException;
use Tomaj\Hermes\Driver\SerializerAwareTrait;
use Tomaj\Hermes\MessageInterface;
use Tomaj\Hermes\MessageSerializer;

class MemoryDriver implements DriverInterface
{
    use SerializerAwareTrait;

    private $events = [];

    private $waitResult = null;

    private $executeAtCheckEnabled = false;

    private $oneLoopDurationSeconds = 60;

    public function __construct($events = null)
    {
        $this->serializer = new MessageSerializer();

        if (!$events) {
            $events = [];
        }
        foreach ($events as $event) {
            $this->events[] = $this->serializer->serialize($event);
        }
    }

    // To simulate execute_at functionality, call this function after initialization
    public function enableExecuteAtCheck($oneLoopDurationSeconds = 60)
    {
        $this->executeAtCheckEnabled = true;
        $this->oneLoopDurationSeconds = $oneLoopDurationSeconds;
    }

    public function send(MessageInterface $message, int $priority = Dispatcher::DEFAULT_PRIORITY): bool
    {
        $this->events[] = $this->serializer->serialize($message);
        return true;
    }

    public function setupPriorityQueue(string $name, int $priority): void
    {
        throw new NotSupportedException();
    }

    public function getMessage()
    {
        $message = array_pop($this->events);
        if (!$message) {
            return null;
        }
        return $this->serializer->unserialize($message);
    }

    public function wait(Closure $callback, array $priorities): void
    {
        $futureMessages = [];

        while (count($this->events) > 0) {
            $event = array_pop($this->events);
            $m = $this->serializer->unserialize($event);

            if ($this->executeAtCheckEnabled && $m->getExecuteAt() > time()) {
                $futureMessages[] = new HermesMessage(
                    $m->getType(),
                    $m->getPayload(),
                    $m->getId(),
                    $m->getCreated(),
                    $m->getExecuteAt() - $this->oneLoopDurationSeconds, // Decrease execute_at time to simulate time pass
                    $m->getRetries()
                );
                continue;
            }

            $this->waitResult = $callback($m);
        }

        // To prevent endless loop in tests, add future messages here
        if ($futureMessages) {
            foreach ($futureMessages as $futureMessage) {
                $this->send($futureMessage);
            }
        }
    }

    public function waitResult()
    {
        return $this->waitResult;
    }
}
