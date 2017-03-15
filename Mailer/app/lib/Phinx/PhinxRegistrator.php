<?php

namespace Remp\MailerModule;

use Phinx\Config\Config;
use Symfony\Component\Console\Application;

class PhinxRegistrator
{

    /** @var array Define phinx commands with aliases */
    private $command = [
        '\Phinx\Console\Command\Init' => 'init',
        '\Phinx\Console\Command\Create' => 'create',
        '\Phinx\Console\Command\Migrate' => 'migrate',
        '\Phinx\Console\Command\Rollback' => 'rollback',
        '\Phinx\Console\Command\Status' => 'status',
        '\Phinx\Console\Command\Test' => 'test'
    ];

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
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
        $env = getenv('CRM_ENV');

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
            'adapter' => getenv('DB_ADAPTER'),
            'host' => getenv('DB_HOST'),
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'pass' => getenv('DB_PASS'),
            'port' => getenv('DB_PORT'),
            'charset' => 'utf8'
        ];

        return $configData;
    }
}
