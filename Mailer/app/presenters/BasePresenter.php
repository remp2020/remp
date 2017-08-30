<?php

namespace Remp\MailerModule\Presenters;

use Kdyby\Autowired\AutowireComponentFactories;
use Kdyby\Autowired\AutowireProperties;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\EnvironmentConfig;

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
            $this->getUser()->authenticator->authenticate([]);
        }

        $this->template->currentUser = $this->getUser();
        $this->template->linkedServices = $this->environmentConfig->getLinkedServices();
    }
}
