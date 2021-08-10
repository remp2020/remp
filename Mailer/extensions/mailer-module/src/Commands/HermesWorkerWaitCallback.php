<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Hermes\RedisDriverWaitCallbackInterface;
use Remp\MailerModule\Models\HealthChecker;

class HermesWorkerWaitCallback implements RedisDriverWaitCallbackInterface
{
    private HealthChecker $healthChecker;

    public function __construct(HealthChecker $healthChecker)
    {
        $this->healthChecker = $healthChecker;
    }

    public function call(): void
    {
        $this->healthChecker->ping(HermesWorkerCommand::COMMAND_NAME);
    }
}
