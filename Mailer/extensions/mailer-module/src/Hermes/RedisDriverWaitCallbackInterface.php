<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

interface RedisDriverWaitCallbackInterface
{
    public function call(): void;
}
