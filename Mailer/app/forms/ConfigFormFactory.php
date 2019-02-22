<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Remp\MailerModule\Config\Config;
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

        $configs = $this->configsRepository->all();
        $container = $form->addContainer('settings');
        $activeMailer = null;

        foreach ($configs as $config) {
            $displayName = $config->display_name ? $config->display_name : $config->name;
            $item = null;

            // handle special cases
            if ($config->name == 'default_mailer') {
                $availableMailers =  $this->mailerFactory->getAvailableMailers();

                $mailers = [];
                array_walk($availableMailers, function ($mailer, $name) use (&$mailers) {
                    $mailers[$name] = get_class($mailer);
                });

                if ($config->value !== null) {
                    $activeMailer = $this->mailerFactory->getMailer($config->value);
                }

                $container->addSelect('default_mailer', 'Default Mailer', $mailers)
                    ->setDefaultValue($config->value);

                continue;
            }

            // handle generic types
            switch ($config->type) :
                case Config::TYPE_STRING:
                case Config::TYPE_PASSWORD:
                    $container->addText($config->name, $displayName)
                        ->setDefaultValue($config->value);
                    break;
                case Config::TYPE_TEXT:
                    $container->addTextArea($config->name, $displayName)
                        ->setDefaultValue($config->value)
                        ->getControlPrototype()
                        ->addAttributes(['class' => 'auto-size']);
                    break;
                case Config::TYPE_HTML:
                    $container->addTextArea($config->name, $displayName)
                        ->setAttribute('rows', 15)
                        ->setDefaultValue($config->value)
                        ->getControlPrototype()
                        ->addAttributes(['class' => 'html-editor']);
                    break;
                case Config::TYPE_BOOLEAN:
                    $container->addCheckbox($config->name, $displayName)
                        ->setDefaultValue($config->value);
                    break;
                default:
                    throw new \Exception('unhandled config type: ' . $config->type);
            endswitch;
        }

        if ($activeMailer !== null) {
            foreach ($activeMailer->getRequiredOptions() as $name) {
                /** @var $comp \Nette\Forms\Controls\BaseControl */
                $comp = $container->getComponent($name);
                $comp->setRequired(true);
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
        foreach ($values['settings'] as $name => $value) {
            $config = $this->configsRepository->loadByName($name);
            if ($config->value != $value) {
                $this->configsRepository->update($config, ['value' => $value]);
            }
        }

        ($this->onSuccess)();
    }
}
