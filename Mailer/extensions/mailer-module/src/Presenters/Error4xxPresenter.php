<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette;
use Remp\MailerModule\Models\Config\LinkedServices;
use Remp\MailerModule\Models\Config\LocalizationConfig;

class Error4xxPresenter extends FrontendPresenter
{
    /** @var LocalizationConfig @inject */
    public $localizationConfig;

    /** @var LinkedServices @inject */
    public $linkedServices;

    public function startup(): void
    {
        parent::startup();

        if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
            $this->error();
        }
    }

    public function renderDefault(
        Nette\Application\BadRequestException $exception,
        ?Nette\Application\UI\Presenter $previousPresenter = null,
    ): void {
        if ($previousPresenter instanceof FrontendPresenter) {
            $this->setLayout('layout_public');
        }

        $this->template->currentUser = $this->getUser();
        $this->template->linkedServices = $this->linkedServices->getServices();
        $this->template->locale = $this->localizationConfig->getDefaultLocale();

        // load template 403.latte or 404.latte or ... 4xx.latte
        $file = __DIR__ . "/templates/Error/{$exception->getCode()}.latte";
        $this->template->setFile(is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte');
    }
}
