<?php

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

(DotEnv::createImmutable(__DIR__ . '/../'))->load();

$environment = $_ENV['ENV'];

if (!$environment) {
    die("You have to specify environment ENV\n");
}

if ($environment !== "test") {
    die("test environment configuration not found\n");
}

$configurator = new Nette\Configurator;
$configurator->setDebugMode(true);
$configurator->setTimeZone(getenv('TIMEZONE'));
$configurator->setTempDirectory(__DIR__ . '/../temp/tests');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/../app')
    ->register();

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
