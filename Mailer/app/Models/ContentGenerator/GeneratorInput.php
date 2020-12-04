<?php

namespace Remp\MailerModule\Models\ContentGenerator;

use Nette\Database\IRow;

class GeneratorInput
{
    private $mailTemplate;

    private $params;

    private $batchId;

    public function __construct(IRow $mailTemplate, array $params = [], ?int $batchId = null)
    {
        $this->mailTemplate = $mailTemplate;
        $this->params = $params;
        $this->batchId = $batchId;
    }

    public function template(): IRow
    {
        return $this->mailTemplate;
    }

    public function layout(): IRow
    {
        return $this->mailTemplate->mail_layout;
    }

    public function batchId(): ?int // what about returning 0?
    {
        return $this->batchId;
    }

    public function params(): array
    {
        return $this->params;
    }
}
