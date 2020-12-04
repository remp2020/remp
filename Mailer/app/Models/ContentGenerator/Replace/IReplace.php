<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

interface IReplace
{
    public function replace(string $content, GeneratorInput $generatorInput): string;
}
