<?php
declare(strict_types=1);

namespace Remp\Mailer\Hermes;

use Remp\MailerModule\Hermes\HermesException;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class MailgunBotTrackerHandler implements HandlerInterface
{
    private const APPLE_BOT_EMAILS = 'apple_bot_emails';

    use RedisClientTrait;

    public function __construct(protected RedisClientFactory $redisClientFactory)
    {
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();

        if (!isset($payload['mail_sender_id'])) {
            // email sent via mailgun and not sent via mailer (e.g. CMS)
            return true;
        }

        if (!isset($payload['event'])) {
            throw new HermesException('unable to handle event: event is missing');
        }
        if ($payload['event'] !== "opened") {
            return true;
        }

        if (isset($payload['client']['bot']) && $payload['client']['bot'] === "apple") {
            $redis = $this->redisClientFactory->getClient();
            $redis->sadd(self::APPLE_BOT_EMAILS, [$payload['email']]);
        }

        return true;
    }
}
