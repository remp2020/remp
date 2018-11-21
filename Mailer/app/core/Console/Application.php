<?php

namespace Remp\MailerModule\Console;

use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;

class Application extends \Symfony\Component\Console\Application
{
    private $container;

    private $commands = [];

    public function __construct(Container $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    public function register($command)
    {
        $this->commands[] = $command;
    }

    public function registerConfiguredCommands()
    {
        foreach ($this->commands as $command) {
            $this->add($command);
        }
    }
}
