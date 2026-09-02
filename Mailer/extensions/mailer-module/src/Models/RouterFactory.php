<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(): RouteList
    {
        $router = new RouteList;

        $router->addRoute(
            mask: '/api/v<version>/<package>[/<apiAction>][/<params>]',
            metadata: 'Api:Api:default',
        );
        $router->addRoute(
            mask: '<module>/<presenter>/<action>[/<id>]',
            metadata: [
                'module' => 'Mailer',
                'presenter' => 'Dashboard',
                'action' => 'default',
                'id' => null,
            ],
        );

        return $router;
    }
}
