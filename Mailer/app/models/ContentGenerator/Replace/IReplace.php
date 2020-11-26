<?php

namespace Remp\MailerModule\ContentGenerator\Replace;

use Remp\MailerModule\ContentGenerator\GeneratorInput;

interface IReplace
{
    public function replace(string $content, GeneratorInput $generatorInput): string;
}
