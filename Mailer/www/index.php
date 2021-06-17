<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

Remp\Mailer\Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run();
