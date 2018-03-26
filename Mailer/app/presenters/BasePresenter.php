<?php

namespace Remp\MailerModule\Presenters;

use Kdyby\Autowired\AutowireComponentFactories;
use Kdyby\Autowired\AutowireProperties;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\EnvironmentConfig;
use Remp\MailerModule\Forms\IFormFactory;

abstract class BasePresenter extends Presenter
{
    use AutowireProperties;
    use AutowireComponentFactories;

    /** @var EnvironmentConfig @inject */
    public $environmentConfig;

    public function __construct()
    {
        parent::__construct();
    }

    public function startup()
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->getUser()->login();
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
    protected function redirectBasedOnButtonSubmitted(string $buttonSubmitted, int $itemID = null)
    {
        if ($buttonSubmitted === IFormFactory::FORM_ACTION_SAVE_CLOSE || is_null($itemID)) {
            $this->redirect('Default');
        } else {
            $this->redirect('Edit', $itemID);
        }
    }
}
