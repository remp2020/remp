<?php

namespace Remp\BeamModule\Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        if ($path = realpath(__DIR__ . '/../../../bootstrap/app.php')) {
            $app = require $path;
        }
        // from vendor
        if ($path = realpath(__DIR__ . '/../../../../bootstrap/app.php')) {
            $app = require $path;
        }

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
