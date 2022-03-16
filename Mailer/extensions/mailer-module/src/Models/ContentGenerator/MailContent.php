<?php

namespace Remp\MailerModule\Models\ContentGenerator;

class MailContent
{
    private string $html;

    private string $text;

    private string $subject;

    public function __construct(string $html, string $text, string $subject)
    {
        $this->html = $html;
        $this->text = $text;
        $this->subject = $subject;
    }

    public function html(): string
    {
        return $this->html;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function subject(): string
    {
        return $this->subject;
    }
}
