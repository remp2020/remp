<?php

namespace Remp\MailerModule\Mailer;

use Nette\Utils\Strings;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Config\ConfigNotExistsException;
use Remp\MailerModule\Repository\ConfigsRepository;

abstract class Mailer
{
    /** @var ConfigsRepository */
    protected $configsRepository;

    /** @var Config */
    protected $config;

    protected $alias;

    protected $options = [];

    public function __construct(
        Config $config,
        ConfigsRepository $configsRepository
    ) {
    
        $this->configsRepository = $configsRepository;
        $this->config = $config;

        $this->buildConfig();
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getConfig()
    {
        return $this->options;
    }

    protected function buildConfig()
    {
        foreach ($this->options as $configName) {
            try {
                $prefix = str_replace('-', '_', Strings::webalize(get_called_class()));
                $this->options[$configName] = $this->config->get($prefix . '_' . $configName);
                unset($this->options[array_search($configName, $this->options)]);
            } catch (ConfigNotExistsException $e) {
                $displayName = substr(get_called_class(), strrpos(get_called_class(), '\\') + 1) . ' ' . Strings::firstUpper($configName);
                $description = 'Setting for ' . get_called_class();
                $this->configsRepository->add($prefix . '_' . $configName, $displayName, null, $description, Config::TYPE_STRING);

                $this->options[$configName] = null;
            }
        }
    }

    /**
     * If Mailer implementation supports template parameters (e.g. within batch email sending)
     * you can replace the real values of params with names of template variables which will
     * be used to inject the values by Mailer service.
     *
     * Return value is ordered as [transformed params for twig,
     * altered params for mailer header X-Mailer-Template-Params]
     *
     * @param $params
     *
     * @return mixed
     */
    public function transformTemplateParams($params)
    {
        return [$params, $params];
    }

    /**
     * supportsBatch returns flag, whether the selected Mailer supports batch sending
     *
     * @return bool
     */
    public function supportsBatch(): bool
    {
        return false;
    }
}
