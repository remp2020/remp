#!/usr/bin/env php
<?php

use Nette\Database\DriverException;
use Remp\MailerModule\PhinxRegistrator;
use Symfony\Component\Console\Application;

$container = require __DIR__ . '/../app/bootstrap.php';

$application = new Application("Mailer");
$application->setCatchExceptions(false);

$input = new \Symfony\Component\Console\Input\ArgvInput();
$output = new \Symfony\Component\Console\Output\ConsoleOutput();

$phinxRegistrator = new PhinxRegistrator($application, $container->getByType('Remp\MailerModule\EnvironmentConfig'));

try {
    /** @var \Remp\MailerModule\Console\Application $consoleApplication */
    $consoleApplication = $container->getByType('Remp\MailerModule\Console\Application');
    $commands = $consoleApplication->getCommands();
    foreach ($commands as $command) {
        $application->add($command);
    }
} catch (DriverException $driverException) {
    $output->getErrorOutput()->writeln("<question>NOTICE:</question> Looks like the new fresh install");
    $output->getErrorOutput()->writeln("<question>NOTICE:</question> Commands limited to migrations.");
} catch (InvalidArgumentException $invalidArgument) {
    $output->getErrorOutput()->writeln("<question>NOTICE:</question> Looks like the new fresh install - or wrong configuration - '{$invalidArgument->getMessage()}'.");
    $output->getErrorOutput()->writeln("<question>NOTICE:</question> Commands limited to migrations.");
}

return $application->run($input, $output);
