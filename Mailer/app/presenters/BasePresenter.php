<?php

namespace Remp\MailerModule\Presenters;

use Kdyby\Autowired\AutowireComponentFactories;
use Kdyby\Autowired\AutowireProperties;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\Components\IDataTableFactory;

abstract class BasePresenter extends Presenter
{
    use AutowireProperties;
    use AutowireComponentFactories;

    public function startup()
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:In');
        }

        $this->template->currentUser = $this->getUser();
    }
}
