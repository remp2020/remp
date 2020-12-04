<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Mailer;

use Nette\Mail\IMailer;
use Nette\Utils\Strings;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Config\ConfigNotExistsException;
use Remp\MailerModule\Repositories\ConfigsRepository;

abstract class Mailer implements IMailer
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

    public function getAlias(): string
    {
        return $this->alias;
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
            $prefix = $this->getPrefix();

            try {
                $this->options[$name]['value'] = $this->config->get($this->getAlias() . '_' . $name);
            } catch (ConfigNotExistsException $e) {
                $this->configsRepository->add(
                    $prefix . '_' . $name,
                    $definition['label'],
                    null,
                    $definition['description'] ?? null,
                    Config::TYPE_STRING
                );

                $this->options[$name] = [
                    'label' => $definition['label'],
                    'required' => $definition['required'],
                    'value' => null,
                ];
            }
        }
    }

    public function getPrefix(): string
    {
        return str_replace('-', '_', Strings::webalize(static::class));
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
