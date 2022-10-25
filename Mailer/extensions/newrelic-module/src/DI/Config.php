<?php
declare(strict_types=1);

namespace Remp\NewrelicModule\DI;

class Config
{
    private bool $logRequestListenerErrors = true;

    public function getLogRequestListenerErrors(): bool
    {
        return $this->logRequestListenerErrors;
    }

    public function setLogRequestListenerErrors(bool $logErrors): void
    {
        $this->logRequestListenerErrors = $logErrors;
    }
}
