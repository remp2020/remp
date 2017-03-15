#!/usr/bin/env php
<?php

use Remp\MailerModule\PhinxRegistrator;
use Symfony\Component\Console\Application;
use Nette\Database\DriverException;
use Nette\InvalidArgumentException;

$container = require __DIR__ . '/../app/bootstrap.php';
$application = new Application();
$application->setCatchExceptions(false);

$phinxRegistrator = new PhinxRegistrator($application);

try {
    $applicationManager = $container->getByType('Crm\ApplicationModule\ApplicationManager');
    $commands = $applicationManager->getCommands();
    foreach ($commands as $command) {
        $application->add($command);
    }
} catch (DriverException $driverException) {
    echo "INFO: Looks like the new fresh install.\n";
} catch (InvalidArgumentException $invalidArgument) {
    echo "INFO: Looks like the new fresh install - or wrong configuration - '{$invalidArgument->getMessage()}'.\n";
}

$application->run();
