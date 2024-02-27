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
    // Default value copied from Tomaj\Hermes\Handler\RetryTrait
    private int $maxRetries = 25;

    public function __construct(
        private TemplatesRepository $templatesRepository,
        private Sender $sender,
    ) {
    }

    public function setMaxRetries(int $maxRetries)
    {
        $this->maxRetries = $maxRetries;
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
            ->setParams($payload['params'] ?? [])
            ->setLocale($payload['locale'] ?? null);

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

    public function canRetry(): bool
    {
        return $this->maxRetries > 0;
    }

    public function maxRetry(): int
    {
        return $this->maxRetries;
    }
}
