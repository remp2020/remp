#!/usr/bin/env php
<?php

use Nette\Database\DriverException;
use Remp\MailerModule\PhinxRegistrator;

$container = require __DIR__ . '/../app/bootstrap.php';

/** @var \Remp\MailerModule\Console\Application $application */
$application = $container->getByType('Remp\MailerModule\Console\Application');

$phinxRegistrator = new PhinxRegistrator($application, $container->getByType('Remp\MailerModule\EnvironmentConfig'));

try {
    $application->registerConfiguredCommands();
} catch (DriverException $driverException) {
    echo "INFO: Looks like the new fresh install.\n";
} catch (InvalidArgumentException $invalidArgument) {
    echo "INFO: Looks like the new fresh install - or wrong configuration - '{$invalidArgument->getMessage()}'.\n";
}

$application->run();
