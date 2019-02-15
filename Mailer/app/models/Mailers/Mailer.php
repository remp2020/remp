<?php

namespace Remp\MailerModule\Mailer;

use Nette\Mail\IMailer;
use Nette\Utils\Strings;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Config\ConfigNotExistsException;
use Remp\MailerModule\Repository\ConfigsRepository;

abstract class Mailer implements IMailer
{
    /** @var ConfigsRepository */
    protected $configsRepository;

    /** @var Config */
    protected $config;

    protected $alias;

    protected $options = [];

    protected $requiredOptions = [];

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
        foreach ($this->options as $optionName) {
            $configKey = $this->getOptionConfigKey($optionName);

            try {
                $this->options[$optionName] = $this->config->get($configKey);

                if (in_array($optionName, $this->requiredOptions)) {
                    $this->requiredOptions[] = $configKey;
                    unset($this->requiredOptions[array_search($optionName, $this->options)]);
                }

                unset($this->options[array_search($optionName, $this->options)]);
            } catch (ConfigNotExistsException $e) {
                $displayName = substr(get_called_class(), strrpos(get_called_class(), '\\') + 1) . ' ' . Strings::firstUpper($optionName);
                $description = 'Setting for ' . get_called_class();
                $this->configsRepository->add($configKey, $displayName, null, $description, Config::TYPE_STRING);

                $this->options[$optionName] = null;
            }
        }
    }

    protected function getOptionConfigKey(string $option)
    {
        $prefix = str_replace('-', '_', Strings::webalize(get_called_class()));
        return $prefix . '_' . $option;
    }

    protected function optionIsRequired(string $option)
    {
        if (in_array($option, $this->requiredOptions)) {
            return true;
        }

        return false;
    }

    public function getRequiredOptions()
    {
        return $this->requiredOptions;
    }

    protected function hasOption(string $option)
    {
        if (array_key_exists($option, $this->options)) {
            return true;
        }

        return false;
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
    public function transformTemplateParams(array $params)
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
