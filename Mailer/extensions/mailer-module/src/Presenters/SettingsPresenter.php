<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Form;
use Remp\MailerModule\Forms\ConfigFormFactory;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Mailer\Mailer;
use Remp\MailerModule\Models\Sender\MailerFactory;

final class SettingsPresenter extends BasePresenter
{
    private $mailerFactory;

    private $configFormFactory;

    private $config;

    public function __construct(MailerFactory $mailerFactory, ConfigFormFactory $configFormFactory, Config $config)
    {
        parent::__construct();
        $this->mailerFactory = $mailerFactory;
        $this->configFormFactory = $configFormFactory;
        $this->config = $config;
    }

    public function renderDefault(): void
    {
        $availableMailers =  $this->mailerFactory->getAvailableMailers();

        $requiredFields = [];
        array_walk($availableMailers, function (Mailer $mailer, $name) use (&$requiredFields) {
            $requiredFields[$name] = $mailer->getRequiredOptions();
        });

        $this->template->requiredFields = $requiredFields;
    }

    public function createComponentConfigForm(): Form
    {
        $form = $this->configFormFactory->create();

        $this->configFormFactory->onSuccess = function () {
            $this->flashMessage('Config was updated.');
            $this->redirect('Settings:default');
        };
        return $form;
    }
}
