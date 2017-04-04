<?php

namespace Remp\MailerModule;

use Phinx\Config\Config;
use Symfony\Component\Console\Application;

class PhinxRegistrator
{
    /** @var EnvironmentConfig */
    private $environmentConfig;

    /** @var array Define phinx commands with aliases */
    private $command = [
        '\Phinx\Console\Command\Init' => 'migrate:init',
        '\Phinx\Console\Command\Create' => 'migrate:create',
        '\Phinx\Console\Command\Migrate' => 'migrate:migrate',
        '\Phinx\Console\Command\Rollback' => 'migrate:rollback',
        '\Phinx\Console\Command\Status' => 'migrate:status',
        '\Phinx\Console\Command\Test' => 'migrate:test'
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
    private function buildConfig()
    {
        $env = getenv('ENV');

        $configData = [
            'paths' => [
                'migrations' => '%%PHINX_CONFIG_DIR%%/../../../migrations',
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
