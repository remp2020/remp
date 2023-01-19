<?php
declare(strict_types=1);

namespace Remp\Mailer;

use Dotenv\Dotenv;
use Nette\Configurator;

final class Bootstrap
{
    public static function boot(): Configurator
    {
        // attempt to fix access rights issues in writable folders caused by different web/cli users writing to logs
        umask(0);

        (Dotenv::createImmutable(__DIR__ . '/../'))->load();

        $configurator = new Configurator;
        $environment = $_ENV['ENV'];

        if ($_ENV['FORCE_HTTPS'] === 'true') {
            $_SERVER['HTTPS'] = 'on';
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
            $_SERVER['SERVER_PORT'] = 443;
        }
        if ($environment === 'local') {
            $configurator->setDebugMode(true);
        } else {
            $configurator->setDebugMode(false);
        }

        $configurator->enableTracy(__DIR__ . '/../log');

        $configurator->setTimeZone($_ENV['TIMEZONE']);
        $configurator->setTempDirectory(__DIR__ . '/../temp');

        $configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();

        // Root config, so MailerModule can register extensions, etc.
        $configurator->addConfig(__DIR__ . '/../vendor/remp/mailer-module/src/config/config.root.neon');

        // Rest of configuration
        $configurator->addConfig(__DIR__ . '/config/config.neon');
        $configurator->addConfig(__DIR__ . '/config/config.local.neon');

        return $configurator;
    }

    public static function isCli()
    {
        return PHP_SAPI === 'cli'
            || PHP_SAPI === 'phpdbg'
            || isset($_SERVER['SHELL'])
            || isset($_SERVER['TERM'])
            || defined('STDIN');
    }
}
