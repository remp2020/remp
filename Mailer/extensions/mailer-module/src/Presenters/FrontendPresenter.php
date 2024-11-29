<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\DI\Attributes\Inject;
use Remp\MailerModule\Components\ApplicationStatus\ApplicationStatus;
use Remp\MailerModule\Components\ApplicationStatus\IApplicationStatusFactory;

abstract class FrontendPresenter extends Presenter
{
    #[Inject]
    public IApplicationStatusFactory $applicationStatusFactory;

    public function createComponentApplicationStatus(): ApplicationStatus
    {
        return $this->applicationStatusFactory->create();
    }
}
