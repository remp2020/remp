<?php

namespace Remp\MailerModule\Hermes;

use Remp\MailerModule\Crm\Client;
use Remp\MailerModule\Repository\LogsRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class ConfirmCrmUserHandler implements HandlerInterface
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
            throw new HermesException('unable to handle event: mail_sender_id is missing');
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

        $log = $this->logsRepository->findBySenderId($payload['mail_sender_id']);
        if (!$log) {
            return false;
        }

        $this->crm->confirmUser($log->email);

        return true;
    }
}
