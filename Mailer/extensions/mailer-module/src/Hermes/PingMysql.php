<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Nette\Database\Context;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class PingMysql implements HandlerInterface
{
    private $database;

    public function __construct(Context $database)
    {
        $this->database = $database;
    }

    public function handle(MessageInterface $message): bool
    {
        $this->database->query('SELECT "heartbeat"');
        return true;
    }
}
