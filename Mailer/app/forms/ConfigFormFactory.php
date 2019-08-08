<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Mailer\Mailer;
use Remp\MailerModule\Repository\ConfigsRepository;
use Remp\MailerModule\Sender\MailerFactory;

class ConfigFormFactory
{
    use SmartObject;

    /** @var ConfigsRepository */
    private $configsRepository;

    /** @var MailerFactory */
    private $mailerFactory;

    public $onSuccess;

    public function __construct(
        ConfigsRepository $configsRepository,
        MailerFactory $mailerFactory
    ) {
        $this->configsRepository = $configsRepository;
        $this->mailerFactory = $mailerFactory;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $settings = $form->addContainer('settings');
        $mailerContainer = $settings->addContainer('Mailer');

        $configs = $this->configsRepository->all()->fetchAssoc('name');

        $mailers = [];
        $availableMailers =  $this->mailerFactory->getAvailableMailers();
        array_walk($availableMailers, function ($mailer, $name) use (&$mailers) {
            $mailers[$name] = get_class($mailer);
        });

        $defaultMailer = $mailerContainer
            ->addSelect('default_mailer', 'Default Mailer', $mailers)
            ->setDefaultValue($configs['default_mailer']['value']);

        unset($configs['default_mailer']);

        /** @var $mailer Mailer */
        foreach ($this->mailerFactory->getAvailableMailers() as $mailer) {
            $label = explode('\\', $mailers[$mailer->getAlias()]);
            $mailerContainer = $settings->addContainer($label[count($label)-1]);

            foreach ($mailer->getConfigs() as $name => $option) {
                $key = $mailer->getPrefix() . '_' . $name;
                $config = $configs[$key];

                if ($config['type'] === 'string') {
                    $item = $mailerContainer
                        ->addText($config['name'], $config['display_name'])
                        ->setOption('description', $config['description'])
                        ->setDefaultValue($config['value']);
                }

                if ($option['required']) {
                    $item->addConditionOn($defaultMailer, Form::EQUAL, $mailer->getAlias())
                        ->setRequired("Field {$name} is required when mailer {$mailers[$mailer->getAlias()]} is selected");
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
                        $othersContainer->addText($config['name'], $config['display_name'])
                            ->setDefaultValue($config['value']);
                        break;
                    case Config::TYPE_TEXT:
                        $othersContainer->addTextArea($config['name'], $config['display_name'])
                            ->setDefaultValue($config['value'])
                            ->getControlPrototype()
                            ->addAttributes(['class' => 'auto-size']);
                        break;
                    case Config::TYPE_HTML:
                        $othersContainer->addTextArea($config['name'], $config['display_name'])
                            ->setAttribute('rows', 15)
                            ->setDefaultValue($config['value'])
                            ->getControlPrototype()
                            ->addAttributes(['class' => 'html-editor']);
                        break;
                    case Config::TYPE_BOOLEAN:
                        $othersContainer->addCheckbox($config['name'], $config['display_name'])
                            ->setDefaultValue($config['value']);
                        break;
                    default:
                        throw new \Exception('unhandled config type: ' . $config['type']);
                endswitch;
            }
        }

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
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
}
