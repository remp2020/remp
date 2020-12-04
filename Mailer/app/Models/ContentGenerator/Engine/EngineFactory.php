<?php

namespace Remp\MailerModule\Models\ContentGenerator\Engine;

use Exception;

final class EngineFactory
{
    /** @var string */
    private $default;

    private $engines = [];

    public function register(string $key, IEngine $engine): void
    {
        $this->engines[$key] = $engine;
    }

    public function engine(?string $key = null): IEngine
    {
        if ($key !== null) {
            if (!isset($this->engines[$key])) {
                throw new Exception("Cannot find template engine '{$key}'");
            }
            return $this->engines[$key];
        }

        if ($this->default) {
            return $this->engines[$this->default];
        }

        throw new Exception("Unable to provide engine. No specific engine was requested and no default was configured.");
    }

    public function defaultEngine(string $type): void
    {
        if (!isset($this->engines[$type])) {
            throw new Exception("Unknown template engine '{$type}'");
        }
        $this->default = $type;
    }
}
