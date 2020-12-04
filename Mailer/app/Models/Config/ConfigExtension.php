<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Config;

use Nette\DI\CompilerExtension;

class ConfigExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        $config = $this->getConfig();

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('config_overrider'))
            ->setType(LocalConfig::class)
            ->setArguments([$config])
            ->setAutowired(true);
    }
}
