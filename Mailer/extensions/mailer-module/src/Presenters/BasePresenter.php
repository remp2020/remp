<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Presenter;
use Remp\MailerModule\Components\MissingConfiguration\IMissingConfigurationFactory;
use Remp\MailerModule\Components\MissingConfiguration\MissingConfiguration;
use Remp\MailerModule\Models\EnvironmentConfig;
use Remp\MailerModule\Forms\IFormFactory;

abstract class BasePresenter extends Presenter
{
    /** @var EnvironmentConfig @inject */
    public $environmentConfig;

    /** @var IMissingConfigurationFactory @inject */
    public $missingConfigurationFactory;

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

    public function createComponentMissingConfiguration(): MissingConfiguration
    {
        return $this->missingConfigurationFactory->create();
    }
}
