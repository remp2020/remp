<?php

declare(strict_types=1);

use Nette\Bootstrap\Configurator;

require_once __DIR__ . '/../vendor/autoload.php';

echo '
Latte linter
------------
';

if ($argc < 2) {
    echo "Usage: latte-lint <path>\n";
    exit(1);
}

$configurator = new Configurator;

$configurator->setDebugMode(true);
$configurator->enableTracy(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->register();

// Root config, so MailerModule can register extensions, etc...
$configurator->addConfig(__DIR__ . '/../vendor/remp/mailer-module/src/config/config.root.neon');
$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app/config/config.test.neon');

$container = $configurator->createContainer();

/** @var \Nette\Bridges\ApplicationLatte\LatteFactory $latteFactory */
$latteFactory = $container->getByName('nette.latteFactory');
$engine = $latteFactory->create();

$debug = in_array('--debug', $argv, true);
$path = $argv[1];
$linter = new Latte\Tools\Linter($engine, $debug);
$ok = $linter->scanDirectory($path);
exit($ok ? 0 : 1);
