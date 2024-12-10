<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Tomaj\Hermes\Emitter;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\Message;
use Tomaj\Hermes\MessageInterface;

class MailgunEventHandler implements HandlerInterface
{
    private $logsRepository;

    private $emitter;

    private $batchTemplatesRepository;

    public function __construct(
        LogsRepository $logsRepository,
        Emitter $emitter,
        BatchTemplatesRepository $batchTemplatesRepository
    ) {
        $this->logsRepository = $logsRepository;
        $this->emitter = $emitter;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mail_sender_id'])) {
            // email sent via mailgun and not sent via mailer (e.g. CMS)
            return true;
        }
        if (!isset($payload['timestamp'])) {
            throw new HermesException('unable to handle event: timestamp is missing');
        }
        if (!isset($payload['event'])) {
            throw new HermesException('unable to handle event: event is missing');
        }

        $log = $this->logsRepository->findBySenderId($payload['mail_sender_id']);
        if (!$log) {
            return false;
        }

        $eventTimestamp = explode('.', (string) $payload['timestamp'])[0];
        $date = DateTime::from($eventTimestamp);

        $mailgunEvent = $this->logsRepository->mapEvent($payload['event'], $payload['reason']);
        if (!$mailgunEvent) {
            return false;
        }

        $this->logsRepository->update($log, [
            $mailgunEvent => $date,
        ]);

        $column = $this->batchTemplatesRepository->mapEvent($mailgunEvent);
        if (isset($column)) {
            $this->batchTemplatesRepository->incrementColumn($column, $log->mail_template_id, $log->mail_job_batch_id);
        }

        if ($payload['event'] === 'dropped') {
            $this->emitter->emit(new Message('email-dropped', ['email' => $log->email]), RedisDriver::PRIORITY_LOW);
        }

        if ($payload['event'] === 'failed' && array_key_exists('severity', $payload) && $payload['severity'] === 'permanent') {
            $this->emitter->emit(new Message('email-dropped', ['email' => $log->email]), RedisDriver::PRIORITY_LOW);
        }

        return true;
    }
}
