<?php

namespace Remp\MailerModule\Models\ContentGenerator;

class MailContent
{
    private $html;

    private $text;

    public function __construct(string $html, string $text)
    {
        $this->html = $html;
        $this->text = $text;
    }

    public function html(): string
    {
        return $this->html;
    }

    public function text(): string
    {
        return $this->text;
    }
}
