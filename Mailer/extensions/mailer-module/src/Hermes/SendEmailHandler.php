<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Models\Sender;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;
use Tracy\Debugger;

class SendEmailHandler implements HandlerInterface
{
    private $templatesRepository;

    private $sender;

    public function __construct(
        TemplatesRepository $templatesRepository,
        Sender $sender
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->sender = $sender;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        $mailTemplate = $this->templatesRepository->getByCode($payload['mail_template_code']);
        if (!$mailTemplate) {
            Debugger::log("could not load mail template: record with code [{$payload['mail_template_code']}] doesn't exist");
            return false;
        }

        $email = $this->sender
            ->reset()
            ->setTemplate($mailTemplate)
            ->addRecipient($payload['email'])
            ->setParams($payload['params'] ?? []);

        if ($mailTemplate->attachments_enabled) {
            foreach ($payload['attachments'] ?? [] as $attachment) {
                $email->addAttachment($attachment['file'], base64_decode($attachment['content']) ?? null);
            }
        }
        if (isset($payload['context'])) {
            $email->setContext($payload['context']);
        }

        $email->send(false);
        return true;
    }
}
