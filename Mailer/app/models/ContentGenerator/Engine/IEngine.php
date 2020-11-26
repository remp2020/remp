<?php

namespace Remp\MailerModule\ContentGenerator\Engine;

interface IEngine
{
    public function render(string $content, array $params = []): string;
}
