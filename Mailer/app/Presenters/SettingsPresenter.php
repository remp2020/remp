<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Form;
use Remp\MailerModule\Forms\ConfigFormFactory;
use Remp\MailerModule\Models\Mailer\Mailer;
use Remp\MailerModule\Models\Sender\MailerFactory;

final class SettingsPresenter extends BasePresenter
{
    /** @var MailerFactory */
    private $mailerFactory;

    public function __construct(MailerFactory $mailerFactory)
    {
        parent::__construct();
        $this->mailerFactory = $mailerFactory;
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

    public function createComponentConfigForm(ConfigFormFactory $configFormFactory): Form
    {
        $form = $configFormFactory->create();

        $configFormFactory->onSuccess = function () {
            $this->flashMessage('Config was updated.');
            $this->redirect('Settings:default');
        };
        return $form;
    }
}
