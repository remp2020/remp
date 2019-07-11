<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Presenter;
use Remp\MailerModule\Forms\SignInFormFactory;

final class SignPresenter extends Presenter
{
    /** @var SignInFormFactory */
    private $signInFormFactory;

    public function __construct(SignInFormFactory $signInFormFactory)
    {
        parent::__construct();
        $this->signInFormFactory = $signInFormFactory;
    }

    public function renderIn()
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('Dashboard:Default');
        }
    }

    public function actionOut()
    {
        $this->getUser()->logout();
        $this->flashMessage('You have been successfully signed out');
        $this->redirect('in');
    }

    public function renderError()
    {
        $this->template->error = $this->request->getParameter('error');
    }

    protected function createComponentSignInForm()
    {
        $form = $this->signInFormFactory->create();

        $presenter = $this;
        $this->signInFormFactory->onSignIn = function ($user) use ($presenter) {
            $presenter->flashMessage("Welcome {$user->email}");
            $presenter->redirect('Dashboard:Default');
        };

        return $form;
    }
}
