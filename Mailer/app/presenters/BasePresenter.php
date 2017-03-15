<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
    public function startup()
    {
        parent::startup();

        // @TODO USER AUTHENTICATION
    }
}
