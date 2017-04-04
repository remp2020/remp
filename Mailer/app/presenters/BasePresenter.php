<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Presenter;
use Remp\MailerModule\DataTable\IDataTableFactory;

abstract class BasePresenter extends Presenter
{
    /**
     * @var IDataTableFactory
     * @inject
     */
    public $dataTableFactory;

    public function startup()
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:In');
        }

        $this->template->currentUser = $this->getUser();
    }

    public function createComponentDataTable()
    {
        return $this->dataTableFactory->create();
    }
}
