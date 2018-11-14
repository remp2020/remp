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

        foreach ($configs as $config) {
            $item = null;
            if ($config->name == 'default_mailer') {
                $mailers = [];
                $availableMailers =  $this->mailerFactory->getAvailableMailers();
                array_walk($availableMailers, function ($mailer, $name) use (&$mailers) {
                    $mailers[$name] = get_class($mailer);
                });

                $item = $container->addSelect('default_mailer', 'Default Mailer', $mailers);
            } elseif (in_array($config->type, [Config::TYPE_STRING, Config::TYPE_PASSWORD])) {
                $item = $container->addText($config->name, $config->display_name ? $config->display_name : $config->name);
            } elseif ($config->type == Config::TYPE_TEXT) {
                $item = $container->addTextArea($config->name, $config->display_name ? $config->display_name : $config->name)
                    ->getControlPrototype()->addAttributes(['class' => 'auto-size']);
            } elseif ($config->type == Config::TYPE_HTML) {
                $item = $container->addTextArea($config->name, $config->display_name ? $config->display_name : $config->name)
                    ->setAttribute('rows', 15)
                    ->getControlPrototype()->addAttributes(['class' => 'html-editor']);
            } elseif ($config->type == Config::TYPE_BOOLEAN) {
                $item = $container->addCheckbox($config->name, $config->display_name ? $config->display_name : $config->name);
            } else {
                throw new \Exception('unhandled config type: ' . $config->type);
            }

            $item->setDefaultValue($config->value);
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
