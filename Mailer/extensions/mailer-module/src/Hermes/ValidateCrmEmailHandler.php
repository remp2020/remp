<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Crm\Client;
use Remp\MailerModule\Models\Crm\UserNotFoundException;
use Remp\MailerModule\Repositories\LogsRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class ValidateCrmEmailHandler implements HandlerInterface
{
    private $logsRepository;

    private $crm;

    public function __construct(
        Client $crmClient,
        LogsRepository $logsRepository
    ) {
        $this->crm = $crmClient;
        $this->logsRepository = $logsRepository;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mail_sender_id'])) {
            return false;
        }
        if (!isset($payload['timestamp'])) {
            throw new HermesException('unable to handle event: timestamp is missing');
        }
        if (!isset($payload['event'])) {
            throw new HermesException('unable to handle event: event is missing');
        }

        if ($payload['event'] !== 'delivered') {
            return true;
        }

        $eventTimestamp = explode('.', (string) $payload['timestamp'])[0];
        $date = DateTime::from($eventTimestamp);

        // Search only recent partitions (events arrive within a few days of sending).
        // findBySenderId automatically falls back to a full scan if not found in the window.
        $since = (clone $date)->sub(new \DateInterval('P' . LogsRepository::SENDER_ID_LOOKUP_WINDOW_DAYS . 'D'));
        $log = $this->logsRepository->findBySenderId($payload['mail_sender_id'], $since);
        if (!$log) {
            return false;
        }

        try {
            $this->crm->validateEmail($log->email);
        } catch (UserNotFoundException $userNotFoundException) {
            // we don't want to schedule retry if user doesn't exist but we still want to track this error
            return false;
        }

        return true;
    }
}
