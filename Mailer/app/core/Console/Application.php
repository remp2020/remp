<?php

namespace Remp\MailerModule\Console;

use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;

class Application extends \Symfony\Component\Console\Application
{
    private $container;

    private $announced = [];

    public function __construct(Container $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    public function announce($command)
    {
        $this->announced[] = $command;
    }

    public function registerAnnounced()
    {
        foreach ($this->announced as $type) {
            /** @var Command $instance */
            $instance = $this->container->getByType($type);
            $this->add($instance);
        }
    }
}
