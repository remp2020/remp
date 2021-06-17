<?php

declare(strict_types=1);

namespace Remp\MailerModule\DI;

use Nette\DI\CompilerExtension;

final class MailerModuleExtension extends CompilerExtension
{
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        // load services from config and register them to Nette\DI Container
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__ . '/../config/config.neon')['services']
        );
    }
}
