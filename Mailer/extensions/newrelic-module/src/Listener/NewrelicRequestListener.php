<?php

declare(strict_types=1);

namespace Remp\NewrelicModule\Listener;

use Nette\Application\Application;
use Nette\Application\Request;
use Remp\Mailer\Bootstrap;
use Remp\NewrelicModule\DI\Config;
use Tracy\Debugger;
use Tracy\ILogger;

final class NewrelicRequestListener
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function onRequest(Application $application, Request $request)
    {
        if (!extension_loaded('newrelic')) {
            if ($this->config->getLogRequestListenerErrors()) {
                Debugger::log("You're using Newrelic module without 'newrelic' PHP extension. Either install the extension or disable the module in app's configuration.", ILogger::WARNING);
            }
            return;
        }

        if (Bootstrap::isCli()) {
            newrelic_name_transaction('$ ' . basename($_SERVER['argv'][0]) . ' ' . implode(' ', array_slice($_SERVER['argv'], 1)));
            newrelic_background_job(true);
            return;
        }

        $params = $request->getParameters();
        $name = $request->getPresenterName() . (isset($params['action']) ? ':' . $params['action'] : '');

        // All API requests are routed through same presenter, append endpoint url.
        if ($name === 'Api:Api:default') {
            $apiEndpoint = 'api/v' . implode('/', [
                    $params['version'] ?? '',
                    $params['package'] ?? '',
                    $params['apiAction'] ?? '',
                ]);
            $name .= " ({$apiEndpoint})";
        }

        newrelic_name_transaction($name);
    }
}
