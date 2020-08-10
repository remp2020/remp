<?php

namespace Remp\MailerModule\Config;

use Nette\DI\CompilerExtension;

class ConfigExtension extends CompilerExtension
{
    public function loadConfiguration()
    {
        $config = $this->getConfig();

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('config_overrider'))
            ->setType(LocalConfig::class)
            ->setArguments([$config])
            ->setAutowired(true);
    }
}
