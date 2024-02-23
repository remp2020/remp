<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Exception;
use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Config\LocalConfig;
use Remp\MailerModule\Repositories\ConfigsRepository;
use Remp\MailerModule\Models\Sender\MailerFactory;

class ConfigFormFactory
{
    use SmartObject;

    public $onSuccess;

    public function __construct(
        private ConfigsRepository $configsRepository,
        private MailerFactory $mailerFactory,
        private LocalConfig $localConfig
    ) {
    }

    public function create(): Form
    {
        $form = new Form;
        $form->addProtection();

        $settings = $form->addContainer('settings');
        $mailerContainer = $settings->addContainer('Mailer');

        $configs = $this->configsRepository->all()->fetchAssoc('name');
        $overriddenConfigs = $this->getOverriddenConfigs($configs);

        $mailers = [];
        $availableMailers =  $this->mailerFactory->getAvailableMailers();
        array_walk($availableMailers, function ($mailer, $name) use (&$mailers) {
            $mailers[$name] = $mailer->getIdentifier();
        });

        $defaultMailerKey = 'default_mailer';
        $defaultMailer = $mailerContainer
            ->addSelect($defaultMailerKey, 'Default Mailer', $mailers)
            ->setDefaultValue($configs[$defaultMailerKey]['value'])
            ->setOption('configOverridden', isset($overriddenConfigs[$defaultMailerKey])
                ? "{$defaultMailerKey}: {$this->localConfig->value($defaultMailerKey)}"
                : false)
            ->setOption('description', 'Can be overwriten in newsletter list detail.');

        unset($configs[$defaultMailerKey]); // remove to avoid double populating in internal section lower.

        foreach ($this->mailerFactory->getAvailableMailers() as $mailer) {
            $mailerContainer = $settings->addContainer($mailer->getIdentifier());

            foreach ($mailer->getConfigs() as $name => $option) {
                $key = $mailer->getMailerAlias() . '_' . $name;
                $config = $configs[$key];
                $configOverridden = isset($overriddenConfigs[$key])
                    ? "{$key}: {$this->localConfig->value($key)}"
                    : false;
                $item = null;

                if ($config['type'] === Config::TYPE_STRING) {
                    $item = $mailerContainer
                        ->addText($config['name'], $config['display_name'])
                        ->setOption('description', $config['description'])
                        ->setOption('configOverridden', $configOverridden)
                        ->setDefaultValue($config['value']);
                }

                if ($item != null && $option['required']) {
                    $item->addConditionOn($defaultMailer, Form::EQUAL, $mailer->getMailerAlias())
                        ->setRequired("Field {$name} is required when mailer {$mailers[$mailer->getMailerAlias()]} is selected");
                }

                unset($configs[$config['name']]);
            }
        }

        if (!empty($configs)) {
            $othersContainer = $settings->addContainer('Internal');

            foreach ($configs as $config) {
                $item = null;

                // handle generic types
                switch ($config['type']) :
                    case Config::TYPE_STRING:
                    case Config::TYPE_PASSWORD:
                        $element = $othersContainer->addText($config['name'], $config['display_name']);
                        break;
                    case Config::TYPE_TEXT:
                        $element = $othersContainer->addTextArea($config['name'], $config['display_name']);
                        $element->getControlPrototype()
                            ->addAttributes(['class' => 'auto-size']);
                        break;
                    case Config::TYPE_HTML:
                        $element = $othersContainer->addTextArea($config['name'], $config['display_name']);
                        $element->setHtmlAttribute('rows', 15)
                            ->getControlPrototype()
                            ->addAttributes(['class' => 'html-editor']);
                        break;
                    case Config::TYPE_BOOLEAN:
                        $element = $othersContainer->addCheckbox($config['name'], $config['display_name']);
                        break;
                    case Config::TYPE_INT:
                        $element = $othersContainer->addText($config['name'], $config['display_name']);
                        $element->addCondition(Form::FILLED)
                            ->addRule(Form::INTEGER);
                        break;
                    case Config::TYPE_SELECT:
                        $selectOptions = $config['options'] ? Json::decode($config['options'], true) : [];
                        $element = $othersContainer->addSelect($config['name'], $config['display_name'] ?? $config['name'], $selectOptions);
                        break;
                    default:
                        throw new Exception('unhandled config type: ' . $config['type']);
                endswitch;

                $element->setDefaultValue($config['value']);
                if (isset($overriddenConfigs[$config['name']])) {
                    $element->setOption('configOverridden', "{$defaultMailerKey}: {$this->localConfig->value($config['name'])}");
                }
                if (isset($config['description'])) {
                    $element->setOption('description', Html::el('span')->setHtml($config['description']));
                }
            }
        }

        $form->addSubmit('save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        foreach ($values['settings'] as $category => $configs) {
            foreach ($configs as $name => $value) {
                $config = $this->configsRepository->loadByName($name);
                if ($config->value != $value) {
                    $this->configsRepository->update($config, ['value' => $value]);
                }
            }
        }

        ($this->onSuccess)();
    }

    protected function getOverriddenConfigs(array $configs): array
    {
        return array_filter($configs, function ($key) {
            return $this->localConfig->exists($key);
        }, ARRAY_FILTER_USE_KEY);
    }
}
