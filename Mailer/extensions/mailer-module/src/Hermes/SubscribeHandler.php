<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\Hermes\Emitter;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class SubscribeHandler implements HandlerInterface
{
    private $emitter;

    private $mailTypesRepository;

    private $mailTemplatesRepository;

    private $mailLogsRepository;

    public function __construct(
        Emitter $emitter,
        MailTypesRepository $mailTypesRepository,
        TemplatesRepository $mailTemplatesRepository,
        LogsRepository $mailLogsRepository
    ) {
        $this->emitter = $emitter;
        $this->mailTypesRepository = $mailTypesRepository;
        $this->mailTemplatesRepository = $mailTemplatesRepository;
        $this->mailLogsRepository = $mailLogsRepository;
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

        if (isset($payload['send_welcome_email']) && !$payload['send_welcome_email']) {
            return true;
        }

        $welcomeEmail = $this->mailTypesRepository->find($payload['mail_type_id'])->subscribe_mail_template;

        if (!$welcomeEmail) {
            return true;
        }

        $today = new DateTime('today');
        $this->emitter->emit(new HermesMessage('send-email', [
            'mail_template_code' => $welcomeEmail->code,
            'email' => $payload['user_email'],
            'context' => "nl_welcome_email.{$payload['user_id']}.{$payload['mail_type_id']}.{$today->format('Ymd')}",
        ]));

        return true;
    }
}
