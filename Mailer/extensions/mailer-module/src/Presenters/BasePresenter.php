<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Presenter;
use Remp\MailerModule\Components\ApplicationStatus\ApplicationStatus;
use Remp\MailerModule\Components\ApplicationStatus\IApplicationStatusFactory;
use Remp\MailerModule\Forms\IFormFactory;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\EnvironmentConfig;

abstract class BasePresenter extends Presenter
{
    /** @var EnvironmentConfig @inject */
    public $environmentConfig;

    /** @var IApplicationStatusFactory @inject */
    public $applicationStatusFactory;

    /** @var PermissionManager @inject */
    public $permissionManager;
    
    public function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->getUser()->login("", "");
        }

        $this->template->currentUser = $this->getUser();
        $this->template->linkedServices = $this->environmentConfig->getLinkedServices();
        $this->template->locale = $this->environmentConfig->getParam('locale');
    }

    /**
     * Redirect based on button clicked by user.
     *
     * @param string   $buttonSubmitted
     * @param int|null $itemID ID of item to which redirect when staying on view
     *                         (if null, redirected to Default view ignoring button)
     * @throws \Nette\Application\AbortException
     */
    protected function redirectBasedOnButtonSubmitted(string $buttonSubmitted, int $itemID = null): void
    {
        if ($buttonSubmitted === IFormFactory::FORM_ACTION_SAVE_CLOSE || is_null($itemID)) {
            $this->redirect('Default');
        } else {
            $this->redirect('Edit', $itemID);
        }
    }

    public function createComponentApplicationStatus(): ApplicationStatus
    {
        return $this->applicationStatusFactory->create();
    }
}
