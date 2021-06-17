<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Phinx\Config\Config;
use Symfony\Component\Console\Application;
use Phinx\Console\Command\Test;
use Phinx\Console\Command\Status;
use Phinx\Console\Command\Rollback;
use Phinx\Console\Command\Migrate;
use Phinx\Console\Command\Create;
use Phinx\Console\Command\Init;

class PhinxRegistrator
{
    /** @var EnvironmentConfig */
    private $environmentConfig;

    /** @var array Define phinx commands with aliases */
    private $command = [
        Init::class => 'migrate:init',
        Create::class => 'migrate:create',
        Migrate::class => 'migrate:migrate',
        Rollback::class => 'migrate:rollback',
        Status::class => 'migrate:status',
        Test::class => 'migrate:test'
    ];

    /**
     * @param Application $application
     * @param EnvironmentConfig $environmentConfig
     */
    public function __construct(Application $application, EnvironmentConfig $environmentConfig)
    {
        $this->environmentConfig = $environmentConfig;
        $config = new Config($this->buildConfig(), __FILE__);

        foreach ($this->command as $class => $commandName) {
            $command = new $class;
            $command->setName($commandName);
            if (is_callable([$command, 'setConfig'])) {
                $command->setConfig($config);
            }
            $application->add($command);
        }
    }

    /**
     * Build phinx config from config.local.neon
     * @return array
     */
    private function buildConfig(): array
    {
        $env = $_ENV['ENV'];

        $configData = [
            'paths' => [
                'migrations' => [
                    '%%PHINX_CONFIG_DIR%%/../../../../app/migrations',
                    // currently the only extensions module
                    '%%PHINX_CONFIG_DIR%%/../../../../vendor/remp/mailer-module/src/migrations',
                ]
            ],
            'environments' => [
                'default_migration_table' => 'phinxlog',
                'default_database' => $env,
            ],
        ];

        $configData['environments'][$env] = [
            'adapter' => $this->environmentConfig->get('DB_ADAPTER'),
            'host' => $this->environmentConfig->get('DB_HOST'),
            'name' => $this->environmentConfig->get('DB_NAME'),
            'user' => $this->environmentConfig->get('DB_USER'),
            'pass' => $this->environmentConfig->get('DB_PASS'),
            'port' => $this->environmentConfig->get('DB_PORT'),
            'charset' => 'utf8'
        ];

        return $configData;
    }
}
