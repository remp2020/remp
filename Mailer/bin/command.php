#!/usr/bin/env php
<?php

use Nette\Database\DriverException;
use Remp\MailerModule\PhinxRegistrator;
use Remp\MailerModule\EnvironmentConfig;
use Remp\MailerModule\Console\Application as RempConsoleApplication;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/../app/Bootstrap.php';

$container = Remp\Bootstrap::boot()
    ->createContainer();

$application = new Application("Mailer");
$application->setCatchExceptions(false);

$input = new ArgvInput();
$output = new ConsoleOutput();

$phinxRegistrator = new PhinxRegistrator($application, $container->getByType(EnvironmentConfig::class));

try {
    /** @var \Remp\MailerModule\Console\Application $consoleApplication */
    $consoleApplication = $container->getByType(RempConsoleApplication::class);
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
