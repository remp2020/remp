#!/usr/bin/env php
<?php

use Remp\MailerModule\PhinxRegistrator;
use Symfony\Component\Console\Application;

$container = require __DIR__ . '/../app/bootstrap.php';
$application = new Application();
$application->setCatchExceptions(false);

$phinxRegistrator = new PhinxRegistrator($application, $container->getByType('Remp\MailerModule\EnvironmentConfig'));

$manager = $container->getByType('Remp\MailerModule\ApplicationManager');
foreach ($manager->getCommands() as $command) {
    $command = $container->getByType($command);
    $application->add($command);
}

$application->run();
