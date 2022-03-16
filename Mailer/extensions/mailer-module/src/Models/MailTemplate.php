<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

class MailTemplate
{
    private string $subject;

    private string $textBody;

    private string $htmlBody;

    public function __construct(
        string $subject,
        string $textBody,
        string $htmlBody
    ) {
        $this->subject = $subject;
        $this->textBody = $textBody;
        $this->htmlBody = $htmlBody;
    }

    public function getSubject(): string
    {
        return $this->subject;
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
