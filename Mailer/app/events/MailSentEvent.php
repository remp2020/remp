<?php

namespace Remp\MailerModule\Events;

class MailSentEvent
{
    private $userId;

    private $email;

    private $templateCode;

    private $batchId;

    private $time;

    public function __construct($userId, $email, $templateCode, $batchId, $time)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->templateCode = $templateCode;
        $this->batchId = $batchId;
        $this->time = $time;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getTemplateCode()
    {
        return $this->templateCode;
    }

    public function getBatchId()
    {
        return $this->batchId;
    }

    public function getTime()
    {
        return $this->time;
    }
}
