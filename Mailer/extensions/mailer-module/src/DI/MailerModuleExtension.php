<?php

declare(strict_types=1);

namespace Remp\MailerModule\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class MailerModuleExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'redis_client_factory' => Expect::structure([
                'prefix' => Expect::string(),
                'replication' => Expect::structure([
                    'service' => Expect::string(),
                    'sentinels' => Expect::arrayOf(Expect::string())
                ])
            ]),
        ]);
    }

    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        // set extension parameters for use in config
        $builder->parameters['redis_client_factory'] = (array) $this->config->redis_client_factory;

        // load services from config and register them to Nette\DI Container
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__ . '/../config/config.neon')['services']
        );
    }
}
