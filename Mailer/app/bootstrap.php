<?php

require __DIR__ . '/../vendor/autoload.php';

$env = new Dotenv\Dotenv(__DIR__ . '/../');
$env->load();

$configurator = new Nette\Configurator;
$environment = getenv('ENV');

if ($environment == 'local') {
    $configurator->setDebugMode(true);
} else {
    $configurator->setDebugMode(false);
}
$configurator->enableTracy(__DIR__ . '/../log');

$configurator->setTimeZone(getenv('TIMEZONE'));
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

$errbitConfig = $container->parameters['errbit'];
Tomaj\Errbit\ErrbitLogger::register($errbitConfig);

return $container;
