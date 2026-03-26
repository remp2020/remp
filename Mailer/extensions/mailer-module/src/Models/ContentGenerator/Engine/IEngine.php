<?php

namespace Remp\MailerModule\Models\ContentGenerator\Engine;

interface IEngine
{
    public function render(string $content, array $params = []): string;

    /**
     * Mark a string as safe HTML so the engine does not escape it when rendering.
     * Use this for pre-processed HTML content passed as template parameters.
     */
    public function markSafe(string $content): \Stringable|string;
}
