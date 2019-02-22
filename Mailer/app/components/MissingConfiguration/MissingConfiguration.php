<?php

namespace Remp\MailerModule\Components;

use Nette\Application\UI\Control;
use Remp\MailerModule\Repository\ConfigsRepository;
use Remp\MailerModule\Sender\MailerFactory;

class MissingConfiguration extends Control
{
    /** @var ConfigsRepository */
    private $configsRepository;

    /** @var MailerFactory */
    private $mailerFactory;

    public function __construct(
        ConfigsRepository $configsRepository,
        MailerFactory $mailerFactory
    ) {
        parent::__construct();
        $this->configsRepository = $configsRepository;
        $this->mailerFactory = $mailerFactory;
    }

    public function render()
    {
        $defaultMailerSetting = $this->configsRepository->loadByName('default_mailer');
        $activeMailer = $this->mailerFactory->getMailer($defaultMailerSetting->value);

        if ($mailerConfigured = $activeMailer->isConfigured()) {
            return;
        }

        $this->template->link = $this->getPresenter()->link('Settings:default');
        $this->template->missingConfigs = !$mailerConfigured;

        $this->template->setFile(__DIR__ . '/missing_configuration.latte');
        $this->template->render();
    }
}
