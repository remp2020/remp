<?php
declare(strict_types=1);

namespace Remp\Mailer\Hermes;

use Remp\MailerModule\Hermes\HermesException;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;
use Tracy\Debugger;

class MailgunBotTrackerHandler implements HandlerInterface
{
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

        if (isset($payload['client']['bot']) && $payload['client']['bot'] !== "") {
            $bot = $payload['client']['bot'];
        } else {
            $bot = "no_bot";
        }
        Debugger::log($bot, 'mailgun-bots');

        return true;
    }
}
