<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

class MailLayout
{
    private string $text;

    private string $html;

    public function __construct(string $text, string $html)
    {
        $this->text = $text;
        $this->html = $html;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getHtml(): string
    {
        return $this->html;
    }
}
