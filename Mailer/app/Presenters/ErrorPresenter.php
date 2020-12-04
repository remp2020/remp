<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette;
use Nette\Application\Responses;
use Remp\MailerModule\Components\MissingConfiguration\IMissingConfigurationFactory;
use Remp\MailerModule\Components\MissingConfiguration\MissingConfiguration;
use Tracy\Debugger;

class ErrorPresenter implements Nette\Application\IPresenter
{
    use Nette\SmartObject;

    public function run(Nette\Application\Request $request)
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

    public function createComponentMissingConfiguration(
        IMissingConfigurationFactory $missingConfigurationFactory
    ): MissingConfiguration {
        return $missingConfigurationFactory->create();
    }
}
