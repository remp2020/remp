<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette;
use Nette\Application\IResponse;
use Nette\Application\Responses;
use Remp\MailerModule\Components\MissingConfiguration\IMissingConfigurationFactory;
use Remp\MailerModule\Components\MissingConfiguration\MissingConfiguration;
use Tracy\Debugger;
use Tracy\ILogger;

class ErrorPresenter implements Nette\Application\IPresenter
{
    use Nette\SmartObject;

    private $missingConfigurationFactory;

    public function __construct(IMissingConfigurationFactory $missingConfigurationFactory)
    {
        $this->missingConfigurationFactory = $missingConfigurationFactory;
    }

    public function run(Nette\Application\Request $request): Nette\Application\Response
    {
        $exception = $request->getParameter('exception');

        if ($exception instanceof Nette\Application\BadRequestException) {
            [$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
            return new Responses\ForwardResponse($request->setPresenterName($module . $sep . 'Error4xx'));
        }

        Debugger::log($exception, Debugger::EXCEPTION);
        return new Responses\CallbackResponse(function () {
            require __DIR__ . '/templates/Error/500.phtml';
        });
    }

    public function createComponentMissingConfiguration(): MissingConfiguration
    {
        return $this->missingConfigurationFactory->create();
    }
}
