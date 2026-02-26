<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

class MailTemplate
{
    private string $from;

    private string $subject;

    private ?string $preheader;

    private string $textBody;

    private string $htmlBody;

    public function __construct(
        string $from,
        string $subject,
        string $textBody,
        string $htmlBody,
        ?string $preheader = null
    ) {
        $this->from = $from;
        $this->subject = $subject;
        $this->textBody = $textBody;
        $this->htmlBody = $htmlBody;
        $this->preheader = $preheader;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getPreheader(): ?string
    {
        return $this->preheader;
    }

    public function getTextBody(): string
    {
        return $this->textBody;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }
}
