<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use Nette\Mail\Message;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Mailer\Mailer;
use Remp\MailerModule\Repositories\ConfigsRepository;

class TestMailer extends Mailer
{
    public const ALIAS = 'remp_test_mailer';

    protected array $sent = [];

    public bool $supportsBatch = true;

    public function __construct(
        Config $config,
        ConfigsRepository $configsRepository,
        ?string $code = null,
    ) {
        parent::__construct($config, $configsRepository, $code);
    }

    public function send(Message $mail): void
    {
        $this->sent[] = $mail;
    }

    public function getSent()
    {
        return $this->sent;
    }

    public function getSentToEmails()
    {
        $emails = [];
        foreach ($this->sent as $sentEmail) {
            $emails = array_merge($emails, array_keys($sentEmail->getHeader('To')));
        }
        return $emails;
    }

    public function clearSent()
    {
        $this->sent = [];
    }

    public function supportsBatch(): bool
    {
        return $this->supportsBatch;
    }
}
