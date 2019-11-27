<?php

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Forms\ConfigFormFactory;
use Remp\MailerModule\Mailer\Mailer;
use Remp\MailerModule\Sender\MailerFactory;

final class SettingsPresenter extends BasePresenter
{
    /** @var MailerFactory */
    private $mailerFactory;

    public function __construct(MailerFactory $mailerFactory)
    {
        parent::__construct();
        $this->mailerFactory = $mailerFactory;
    }

    public function renderDefault()
    {
        $availableMailers =  $this->mailerFactory->getAvailableMailers();

        $requiredFields = [];
        array_walk($availableMailers, function ($mailer, $name) use (&$requiredFields) {
            /** @var $mailer Mailer */
            $requiredFields[$name] = $mailer->getRequiredOptions();
        });

        $this->template->requiredFields = $requiredFields;
    }

    public function createComponentConfigForm(ConfigFormFactory $configFormFactory)
    {
        $form = $configFormFactory->create();

        $configFormFactory->onSuccess = function () {
            $this->flashMessage('Config was updated.');
            $this->redirect('Settings:default');
        };
        return $form;
    }
}
