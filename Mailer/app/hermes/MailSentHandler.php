<?php

namespace Remp\MailerModule\Hermes;

use Psr\Log\LoggerAwareTrait;
use Remp\MailerModule\Tracker\EventOptions;
use Remp\MailerModule\Tracker\ITracker;
use Remp\MailerModule\Tracker\User;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class MailSentHandler implements HandlerInterface
{
    use LoggerAwareTrait;

    private $tracker;

    public function __construct(ITracker $tracker) {
        $this->tracker = $tracker;
    }

    public function handle(MessageInterface $message)
    {
        $payload = $message->getPayload();
        if (!isset($payload['user_id'])) {
            throw new HermesException('unable to handle event: user_id is missing');
        }
        if (!isset($payload['email'])) {
            throw new HermesException('unable to handle event: email is missing');
        }
        if (!isset($payload['template_code'])) {
            throw new HermesException('unable to handle event: template_code is missing');
        }
        if (!isset($payload['mail_job_batch_id'])) {
            throw new HermesException('unable to handle event: mail_job_batch_id is missing');
        }

        $options = new EventOptions();
        $options->setUser(new User([
            'id' => $payload['user_id'],
        ]));
        $options->setFields([
            'email' => $payload['email'],
            'template_code' => $payload['template_code'],
            'mail_job_batch_id' => $payload['mail_job_batch_id'],
        ]);
        $this->tracker->trackEvent(
            new \DateTime(),
            'mail',
            'sent',
            $options
        );

        return true;
    }
}
