<?php

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

    public function handle(MessageInterface $message)
    {
        $this->database->query('SELECT "heartbeat"');
        return true;
    }
}
