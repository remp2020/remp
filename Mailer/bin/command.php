<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use Nette\Database\DriverException;
use Remp\MailerModule\Models\PhinxRegistrator;
use Remp\MailerModule\Models\EnvironmentConfig;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$container = Remp\MailerModule\Bootstrap::boot()
    ->createContainer();

$input = new ArgvInput();
$output = new ConsoleOutput();

try {
    $application = $container->getByType(Application::class);
    $phinxRegistrator = new PhinxRegistrator($application, $container->getByType(EnvironmentConfig::class));
    $application->run($input, $output);
} catch (DriverException | InvalidArgumentException $e) {
    $output->getErrorOutput()->writeln("<question>NOTICE:</question> Looks like the new fresh install - or wrong configuration - '{$e->getMessage()}'.");
    $output->getErrorOutput()->writeln("<question>NOTICE:</question> Commands limited to migrations.");
    $application = new Application('mailer-migration');
    $phinxRegistrator = new PhinxRegistrator($application, $container->getByType(EnvironmentConfig::class));
    $application->run($input, $output);
}
