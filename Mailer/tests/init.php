<?php

// attempt to fix access rights issues in writable folders caused by different web/cli users writing to logs
use Dotenv\Dotenv;
use Nette\Bootstrap\Configurator;

umask(0);

(Dotenv::createImmutable(__DIR__))->load();

$configurator = new Configurator;
$environment = $_ENV['ENV'];

$configurator->setDebugMode(true);
$configurator->enableTracy(__DIR__ . '/../log');
$configurator->setTimeZone($_ENV['TIMEZONE']);
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->register();

// Root config, so MailerModule can register extensions, etc...
$configurator->addConfig(__DIR__ . '/../vendor/remp/mailer-module/src/config/config.root.neon');

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app/config/config.test.neon');

$container = $configurator->createContainer();

$GLOBALS['configurator'] = $configurator;
$GLOBALS['container'] = $configurator->createContainer();
