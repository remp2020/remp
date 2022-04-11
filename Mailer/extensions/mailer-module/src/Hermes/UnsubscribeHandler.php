<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Tomaj\Hermes\Emitter;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class UnsubscribeHandler implements HandlerInterface
{
    private $emitter;

    private $mailTypesRepository;

    public function __construct(
        Emitter $emitter,
        MailTypesRepository $mailTypesRepository
    ) {
        $this->emitter = $emitter;
        $this->mailTypesRepository = $mailTypesRepository;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();

        if (!isset($payload['user_id'])) {
            throw new HermesException('unable to handle event: user_id is missing');
        }

        if (!isset($payload['user_email'])) {
            throw new HermesException('unable to handle event: user_email is missing');
        }

        if (!isset($payload['mail_type_id'])) {
            throw new HermesException('unable to handle event: mail_type_id is missing');
        }

        if (isset($payload['send_goodbye_email']) && !$payload['send_goodbye_email']) {
            return true;
        }

        $goodbyeEmail = $this->mailTypesRepository->find($payload['mail_type_id'])->unsubscribe_mail_template;

        if (!$goodbyeEmail) {
            return true;
        }

        $today = new DateTime('today');
        $this->emitter->emit(new HermesMessage('send-email', [
            'mail_template_code' => $goodbyeEmail->code,
            'email' => $payload['user_email'],
            'context' => "nl_goodbye_email.{$payload['user_id']}.{$payload['mail_type_id']}.{$today->format('Ymd')}",
        ]));

        return true;
    }
}
