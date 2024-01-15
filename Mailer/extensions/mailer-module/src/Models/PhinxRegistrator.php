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

    private $appRootDir;

    /**
     * @param Application       $application
     * @param EnvironmentConfig $environmentConfig
     * @param string|null       $appRootDir
     */
    public function __construct(
        Application $application,
        EnvironmentConfig $environmentConfig,
        string $appRootDir = null
    ) {

        if (is_null($appRootDir)) {
            // TODO: [refactoring] try to solve this with some ENV / config variable? or change appRootDir to required?
            // working with assumption callers will be in placed in default mailer-skeleton directories:
            // - <path-to-project>/bin/command.php
            // - <path-to-project>/app/bootstrap.php
            $appRootDir = realpath(dirname($_SERVER["SCRIPT_FILENAME"]) . '/../');
        }
        $this->appRootDir = $appRootDir;

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
            'feature_flags' => [
                'unsigned_primary_keys' => false,
            ],
            'paths' => [
                'migrations' => [
                    $this->appRootDir . '/app/migrations',
                    // currently the only extensions module
                    $this->appRootDir . '/vendor/remp/mailer-module/src/migrations',
                ]
            ],
            'environments' => [
                'default_migration_table' => 'phinxlog',
                'default_environment' => $env,
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
