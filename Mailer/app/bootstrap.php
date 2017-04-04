<?php

require __DIR__ . '/../vendor/autoload.php';

$env = new Dotenv\Dotenv(__DIR__ . '/../');
$env->load();

$configurator = new Nette\Configurator;

$configurator->setDebugMode(true); // enable for your remote IP
$configurator->enableTracy(__DIR__ . '/../log');

$configurator->setTimeZone('Europe/Bratislava');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
