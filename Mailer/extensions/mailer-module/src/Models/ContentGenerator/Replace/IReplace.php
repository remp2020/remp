<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

interface IReplace
{
    /**
     * @param string $content
     * @param GeneratorInput $generatorInput
     * @param array|null $context random array with additional information for replacers
     * @return string
     */
    public function replace(string $content, GeneratorInput $generatorInput, array $context = null): string;
}
