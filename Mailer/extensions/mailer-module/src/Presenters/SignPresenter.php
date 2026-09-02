<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Remp\MailerModule\Forms\SignInFormFactory;

final class SignPresenter extends Presenter
{
    /** @var SignInFormFactory */
    private $signInFormFactory;

    private $httpRequest;

    public function __construct(SignInFormFactory $signInFormFactory, Request $httpRequest)
    {
        parent::__construct();
        $this->signInFormFactory = $signInFormFactory;
        $this->httpRequest = $httpRequest;
    }

    public function renderIn(): void
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('Dashboard:Default');
        }
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('You have been successfully signed out');
        $this->redirect('in');
    }

    public function renderError(): void
    {
        $this->template->error = $this->httpRequest->getQuery('error');
    }

    protected function createComponentSignInForm(): Form
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
