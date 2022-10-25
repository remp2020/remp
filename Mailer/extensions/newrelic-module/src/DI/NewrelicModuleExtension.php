<?php

declare(strict_types=1);

namespace Remp\NewrelicModule\DI;

use Nette;
use Nette\Application\Application;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Remp\NewrelicModule\Listener\NewrelicRequestListener;
use Tracy\Debugger;

final class NewrelicModuleExtension extends CompilerExtension
{
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        $config = $builder->addDefinition('newrelicConfig')
            ->setType(Config::class);

        if (isset($this->config->logRequestListenerErrors)) {
            $config->addSetup('setLogRequestListenerErrors', [$this->config->logRequestListenerErrors]);
        }
    }

    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'logRequestListenerErrors' => Nette\Schema\Expect::bool(true)->dynamic()
        ]);
    }

    public function beforeCompile(): void
    {
        parent::beforeCompile();

        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('newrelicRequestListener'))
            ->setType(NewrelicRequestListener::class)
            ->setAutowired(false);
        $applicationService = $builder->getByType(Application::class) ?: 'application';

        if ($builder->hasDefinition($applicationService)) {
            $applicationServiceDefinition = $builder->getDefinition($applicationService);
            if (!$applicationServiceDefinition instanceof ServiceDefinition) {
                Debugger::log(
                    "Unable to initialize NewrelicModuleExtension, 'application' is not a service definition",
                    Debugger::ERROR
                );
                return;
            }
            $applicationServiceDefinition->addSetup(
                '$service->onRequest[] = ?',
                [
                    [$this->prefix('@newrelicRequestListener'), 'onRequest'],
                ]
            );
        }
    }
}
