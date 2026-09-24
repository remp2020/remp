<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Remp\MailerModule\Forms\SignInFormFactory;
use Remp\MailerModule\Models\Auth\SignOutUrlProviderInterface;

final class SignPresenter extends Presenter
{
    public function __construct(
        private readonly SignInFormFactory $signInFormFactory,
        private readonly Request $httpRequest,
        private readonly ?SignOutUrlProviderInterface $signOutUrlProvider = null,
    ) {
        parent::__construct();
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
        $this->template->signOutUrl = $this->signOutUrlProvider?->getSignOutUrl($this->link('//Dashboard:Default'));
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
