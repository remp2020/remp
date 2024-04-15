<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Mailer;

use Nette\Utils\Strings;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Config\ConfigNotExistsException;
use Remp\MailerModule\Repositories\ConfigsRepository;

abstract class Mailer implements \Nette\Mail\Mailer
{
    public const ALIAS = "";

    protected array $options = [];

    protected ?string $code = null;

    public function __construct(
        private Config $config,
        private ConfigsRepository $configsRepository,
        ?string $code = null,
    ) {
        $this->code = $code;
        $this->buildConfig();
    }

    public function getMailerAlias(): string
    {
        return self::buildAlias($this::ALIAS, $this->code);
    }

    public static function buildAlias($alias, $code)
    {
        $mailerAlias =  str_replace('-', '_', Strings::webalize($alias));
        if (isset($code)) {
            $mailerAlias .= '_' . $code;
        }
        return $mailerAlias;
    }

    public function getIdentifier(): string
    {
        $array = explode('\\', get_class($this));
        $label = end($array);
        if (isset($this->code)) {
            $label .= '_' . $this->code;
        }

        return $label;
    }

    public function getConfigs(): array
    {
        return $this->options;
    }

    /**
     * Returns single config value
     *
     * @param string $config
     * @return string|null
     */
    public function getConfig(string $config): ?string
    {
        return $this->options[$config]['value'] ?? null;
    }

    protected function buildConfig(): void
    {
        foreach ($this->options as $name => $definition) {
            $configName = $this->getConfigFieldName($name);

            try {
                $this->options[$name]['value'] = $this->config->get($configName);
            } catch (ConfigNotExistsException $e) {
                $this->configsRepository->add(
                    $configName,
                    $definition['label'],
                    null,
                    $definition['description'] ?? null,
                    Config::TYPE_STRING
                );
                $this->config->refresh(true);

                $this->options[$name] = [
                    'label' => $definition['label'],
                    'required' => $definition['required'],
                    'value' => null,
                ];
            }
        }
    }

    private function getConfigFieldName(string $name): string
    {
        return $this->getMailerAlias() . '_' . $name;
    }

    public function isConfigured(): bool
    {
        foreach ($this->getRequiredOptions() as $option) {
            if (!isset($option['value'])) {
                return false;
            }
        }

        return true;
    }

    public function getRequiredOptions(): array
    {
        return array_filter($this->options, function ($option) {
            return $option['required'];
        });
    }

    /**
     * If Mailer implementation supports template parameters (e.g. within batch email sending)
     * you can replace the real values of params with names of template variables which will
     * be used to inject the values by Mailer service.
     *
     * Return value is ordered as [transformed params for twig,
     * altered params for mailer header X-Mailer-Template-Params]
     *
     * @param array $params
     * @return array
     */
    public function transformTemplateParams(array $params): array
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
