<?php

declare(strict_types=1);

namespace Remp\MailerModule\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\InvalidConfigurationException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Remp\MailerModule\Latte\PermissionMacros;

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

    public function beforeCompile()
    {
        parent::beforeCompile();

        $builder = $this->getContainerBuilder();
        $latteFactoryDefinition = $builder->getDefinition('latte.latteFactory');
        if (!$latteFactoryDefinition instanceof FactoryDefinition) {
            throw new InvalidConfigurationException(
                sprintf(
                    'latte.latteFactory service definition must be of type %s, not %s',
                    FactoryDefinition::class,
                    get_class($latteFactoryDefinition)
                )
            );
        }

        $permissionmanager = $builder->getDefinition('permissionManager');
        $resultDefinition = $latteFactoryDefinition->getResultDefinition();
        $resultDefinition
            ->addSetup('?->addProvider(?, ?)', ['@self', 'permissionManager', $permissionmanager])
            ->addSetup('?->onCompile[] = function ($engine) { ' . PermissionMacros::class . '::install($engine->getCompiler()); }', ['@self']);
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
