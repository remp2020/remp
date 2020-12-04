<?php
declare(strict_types=1);

namespace Remp\MailerModule;

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

        // terminal
        if (!isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SHELL'])) {
            $configurator->setDebugMode(true);
        }

        $configurator->enableTracy(__DIR__ . '/../log');

        $configurator->setTimeZone($_ENV['TIMEZONE']);
        $configurator->setTempDirectory(__DIR__ . '/../temp');

        $configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();

        $configurator->addConfig(__DIR__ . '/config/config.neon');
        $configurator->addConfig(__DIR__ . '/config/config.local.neon');

        return $configurator;
    }
}
