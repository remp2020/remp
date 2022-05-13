<?php

declare(strict_types=1);

namespace Remp\NewrelicModule\DI;

use Remp\NewrelicModule\Listener\NewrelicRequestListener;
use Nette\Application\Application;
use Nette\DI\CompilerExtension;

final class NewrelicModuleExtension extends CompilerExtension
{
    public function beforeCompile(): void
    {
        parent::beforeCompile();

        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('newrelicRequestListener'))
            ->setType(NewrelicRequestListener::class)
            ->setAutowired(false);
        $applicationService = $builder->getByType(Application::class) ?: 'application';
        if ($builder->hasDefinition($applicationService)) {
            $builder->getDefinition($applicationService)
                ->addSetup('$service->onRequest[] = ?', [[$this->prefix('@newrelicRequestListener'), 'onRequest']]);
        }
    }
}
