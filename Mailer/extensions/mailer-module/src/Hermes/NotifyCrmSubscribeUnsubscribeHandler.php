<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Remp\MailerModule\Models\Crm\Client;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;
use Tomaj\Hermes\MessageInterface;
use Tomaj\Hermes\Handler\HandlerInterface;

class NotifyCrmSubscribeUnsubscribeHandler implements HandlerInterface
{
    use RedisClientTrait;

    private array $allowedMessageTypes = [
        'user-subscribed',
        'user-unsubscribed',
        'user-subscribed-variant',
        'user-unsubscribed-variant',
    ];

    public function __construct(
        private Client $client,
        protected RedisClientFactory $redisClientFactory
    ) {
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();

        if (!in_array($message->getType(), $this->allowedMessageTypes, true)) {
            throw new HermesException(
                "unable to handle event: wrong type '{$message->getType()}', only " . implode(',', $this->allowedMessageTypes) . " types are allowed"
            );
        }

        if (!isset($payload['user_id'])) {
            throw new HermesException('unable to handle event: user_id is missing');
        }

        $key = "user_touch_call:" . $payload['user_id'];
        if ($this->redis()->get($key)) {
            return true;
        }

        $this->redis()->setex($key, 10, 1);

        $this->client->userTouch($payload['user_id']);

        return true;
    }
}
