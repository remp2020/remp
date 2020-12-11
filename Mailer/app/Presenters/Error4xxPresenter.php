<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette;
use Remp\MailerModule\Components\MissingConfiguration\IMissingConfigurationFactory;
use Remp\MailerModule\Components\MissingConfiguration\MissingConfiguration;
use Remp\MailerModule\Models\EnvironmentConfig;

class Error4xxPresenter extends BasePresenter
{
    /** @var EnvironmentConfig @inject */
    public $environmentConfig;

    public function startup(): void
    {
        parent::startup();

        if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
            $this->error();
        }
    }

    public function renderDefault(Nette\Application\BadRequestException $exception): void
    {
        $this->template->currentUser = $this->getUser();
        $this->template->linkedServices = $this->environmentConfig->getLinkedServices();
        $this->template->locale = $this->environmentConfig->getParam('locale');

        // load template 403.latte or 404.latte or ... 4xx.latte
        $file = __DIR__ . "/templates/Error/{$exception->getCode()}.latte";
        $this->template->setFile(is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte');
    }
}
