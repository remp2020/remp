<?php
declare(strict_types=1);

namespace Remp\Mailer\Hermes;

use DateInterval;
use Nette\Utils\DateTime;
use Remp\MailerModule\Hermes\HermesException;
use Remp\MailerModule\Models\Crm\Client;
use Remp\MailerModule\Models\Crm\UserNotFoundException;
use Remp\MailerModule\Repositories\LogsRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class ConfirmCrmUserHandler implements HandlerInterface
{
    public function __construct(
        private readonly LogsRepository $logsRepository,
        private readonly Client $crmClient,
    ) {
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mail_sender_id'])) {
            // Email sent via mailgun and not sent via mailer (e.g. CMS)
            return true;
        }
        if (!isset($payload['timestamp'])) {
            throw new HermesException('unable to handle event: timestamp is missing');
        }
        if (!isset($payload['event'])) {
            throw new HermesException('unable to handle event: event is missing');
        }

        if ($payload['event'] !== 'clicked') {
            return true;
        }

        $eventTimestamp = explode('.', (string) $payload['timestamp'])[0];
        $date = DateTime::from($eventTimestamp);

        $since = (clone $date)->sub(new DateInterval('P' . LogsRepository::SENDER_ID_LOOKUP_WINDOW_DAYS . 'D'));
        $log = $this->logsRepository->findBySenderId($payload['mail_sender_id'], $since);
        if (!$log) {
            return false;
        }

        try {
            $this->crmClient->confirmUser($log->email);
        } catch (UserNotFoundException) {
            // We don't want to schedule retry if user doesn't exist, but we still want to track this error
            return false;
        }

        return true;
    }
}
