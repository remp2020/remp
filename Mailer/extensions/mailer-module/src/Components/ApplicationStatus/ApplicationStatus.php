<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\ApplicationStatus;

use Nette\Application\UI\Control;
use Remp\MailerModule\Commands\HermesWorkerCommand;
use Remp\MailerModule\Commands\MailWorkerCommand;
use Remp\MailerModule\Commands\ProcessJobCommand;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\HealthChecker;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Repositories\ListsRepository;

class ApplicationStatus extends Control
{
    private HealthChecker $healthChecker;

    private Config $config;

    private ListsRepository $listsRepository;

    private MailerFactory $mailerFactory;

    public function __construct(
        Config $config,
        HealthChecker $healthChecker,
        MailerFactory $mailerFactory,
        ListsRepository $listsRepository
    ) {
        $this->healthChecker = $healthChecker;
        $this->config = $config;
        $this->listsRepository = $listsRepository;
        $this->mailerFactory = $mailerFactory;
    }

    public function render(): void
    {
        // Missing configuration
        $this->template->unconfiguredMailer = $this->getUnconfiguredMailer();
        $this->template->settingsLink = $this->getPresenter()->link('Settings:default');
        
        // Application status
        $apps = [
            ProcessJobCommand::COMMAND_NAME,
            HermesWorkerCommand::COMMAND_NAME,
            MailWorkerCommand::COMMAND_NAME,
        ];
        $onlineStatuses = [];
        
        $online = true;
        foreach ($apps as $app) {
            $onlineStatuses[$app] = $this->healthChecker->isHealthy($app);
            $online = $online && $onlineStatuses[$app];
        }
        
        $this->template->onlineStatuses = $onlineStatuses;
        $this->template->online = $online;
        $this->template->setFile(__DIR__ . '/application_status.latte');
        $this->template->render();
    }
    
    private function getUnconfiguredMailer(): ?string
    {
        $defaultMailerSetting = $this->config->get('default_mailer');
        if ($defaultMailerSetting !== null) {
            $activeMailer = $this->mailerFactory->getMailer($defaultMailerSetting);

            if (!$activeMailer->isConfigured()) {
                return $activeMailer->getIdentifier();
            }
        }

        $usedMailersAliases = $this->listsRepository->getUsedMailersAliases();
        foreach ($usedMailersAliases as $mailerAlias) {
            $mailer = $this->mailerFactory->getMailer($mailerAlias);

            if (!$mailer->isConfigured()) {
                return $mailer->getIdentifier();
            }
        }
        return null;
    }
}
