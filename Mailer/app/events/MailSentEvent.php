<?php
declare(strict_types=1);

namespace Remp\MailerModule\Events;

class MailSentEvent
{
    private $userId;

    private $email;

    private $templateCode;

    private $batchId;

    private $time;

    public function __construct(int $userId, string $email, string $templateCode, int $batchId, int $time)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->templateCode = $templateCode;
        $this->batchId = $batchId;
        $this->time = $time;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTemplateCode(): string
    {
        return $this->templateCode;
    }

    public function getBatchId(): int
    {
        return $this->batchId;
    }

    public function getTime(): int
    {
        return $this->time;
    }
}
