<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;

class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(): RouteList
    {
        $router = new RouteList;
        $router[] = new Route('<presenter>/<action>[/<id>]', [
            'module' => 'Mailer',
            'presenter' => 'Dashboard',
            'action' => 'default',
            'id' => null,
        ]);

        $router[] = new Route('/api/v<version>/<package>[/<apiAction>][/<params>]', 'Api:Api:default');
        return $router;
    }
}
