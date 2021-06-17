<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\MissingConfiguration;

use Nette\Application\UI\Control;
use Remp\MailerModule\Repositories\ConfigsRepository;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Repositories\ListsRepository;

class MissingConfiguration extends Control
{
    /** @var ConfigsRepository */
    private $configsRepository;

    /** @var MailerFactory */
    private $mailerFactory;

    private $listsRepository;

    public function __construct(
        ConfigsRepository $configsRepository,
        MailerFactory $mailerFactory,
        ListsRepository $listsRepository
    ) {
        $this->configsRepository = $configsRepository;
        $this->mailerFactory = $mailerFactory;
        $this->listsRepository = $listsRepository;
    }

    public function render(): void
    {
        $this->template->link = $this->getPresenter()->link('Settings:default');
        $this->template->setFile(__DIR__ . '/missing_configuration.latte');

        $defaultMailerSetting = $this->configsRepository->loadByName('default_mailer');
        if ($defaultMailerSetting->value !== null) {
            $activeMailer = $this->mailerFactory->getMailer($defaultMailerSetting->value);

            if (!$activeMailer->isConfigured()) {
                $this->template->mailerIdentifier = $activeMailer->getIdentifier();
                $this->template->render();
                return;
            }
        }

        $usedMailersAliases = $this->listsRepository->getUsedMailersAliases();
        foreach ($usedMailersAliases as $mailerAlias) {
            $mailer = $this->mailerFactory->getMailer($mailerAlias);

            if (!$mailer->isConfigured()) {
                $this->template->mailerIdentifier = $mailer->getIdentifier();
                $this->template->render();
                return;
            }
        }
    }
}
