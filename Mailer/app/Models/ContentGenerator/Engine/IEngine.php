<?php

namespace Remp\MailerModule\Models\ContentGenerator\Engine;

interface IEngine
{
    public function render(string $content, array $params = []): string;
}
