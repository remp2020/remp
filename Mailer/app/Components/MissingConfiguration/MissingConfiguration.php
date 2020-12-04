<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\MissingConfiguration;

use Nette\Application\UI\Control;
use Remp\MailerModule\Repositories\ConfigsRepository;
use Remp\MailerModule\Models\Sender\MailerFactory;

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

    public function render(): void
    {
        $defaultMailerSetting = $this->configsRepository->loadByName('default_mailer');
        $mailerConfigured = false;

        if ($defaultMailerSetting->value !== null) {
            $activeMailer = $this->mailerFactory->getMailer($defaultMailerSetting->value);

            if ($mailerConfigured = $activeMailer->isConfigured()) {
                return;
            }
        }

        $this->template->link = $this->getPresenter()->link('Settings:default');
        $this->template->missingConfigs = !$mailerConfigured;

        $this->template->setFile(__DIR__ . '/missing_configuration.latte');
        $this->template->render();
    }
}
