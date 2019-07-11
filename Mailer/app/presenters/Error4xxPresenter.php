<?php

namespace Remp\MailerModule\Presenters;

use Nette;
use Remp\MailerModule\Components\IMissingConfigurationFactory;
use Remp\MailerModule\EnvironmentConfig;

class Error4xxPresenter extends Nette\Application\UI\Presenter
{
    /** @var EnvironmentConfig @inject */
    public $environmentConfig;

    public function startup()
    {
        parent::startup();

        if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
            $this->error();
        }
    }

    public function renderDefault(Nette\Application\BadRequestException $exception)
    {
        $config = $this->context->getByType(EnvironmentConfig::class);

        $this->template->currentUser = $this->getUser();
        $this->template->linkedServices = $config->getLinkedServices();
        $this->template->locale = $config->getParam('locale');

        // load template 403.latte or 404.latte or ... 4xx.latte
        $file = __DIR__ . "/templates/Error/{$exception->getCode()}.latte";
        $this->template->setFile(is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte');
    }

    public function createComponentMissingConfiguration(
        IMissingConfigurationFactory $missingConfigurationFactory
    ) {
        return $missingConfigurationFactory->create();
    }
}
