<?php

require __DIR__ . '/../vendor/autoload.php';

$env = new Dotenv\Dotenv(__DIR__ . '/../');
$env->load();

// attempt to fix access rights issues in writable folders caused by different web/cli users writing to logs
umask(0);

$configurator = new Nette\Configurator;
$environment = getenv('ENV');

if (getenv('FORCE_HTTPS') === 'true') {
    $_SERVER['HTTPS'] = true;
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    $_SERVER['SERVER_PORT'] = 443;
}
if ($environment == 'local') {
    $configurator->setDebugMode(true);
} else {
    $configurator->setDebugMode(false);
}

// terminal
if (!isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SHELL'])) {
    $configurator->setDebugMode(true);
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
