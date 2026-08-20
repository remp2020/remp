<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Form;
use Remp\MailerModule\Forms\ConfigFormFactory;
use Remp\MailerModule\Models\Mailer\Mailer;
use Remp\MailerModule\Models\Sender\MailerFactory;

final class SettingsPresenter extends BasePresenter
{
    public function __construct(
        private readonly MailerFactory $mailerFactory,
        private readonly ConfigFormFactory $configFormFactory,
    ) {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        if (!$this->permissionManager->isAllowed($this->user, 'configuration', 'update')) {
            $this->flashMessage("You do not have permission to manage mailers.", 'warning');
            $this->redirect(':Mailer:Dashboard:default');
        }

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
