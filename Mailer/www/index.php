<?php

require __DIR__ . '/../app/Bootstrap.php';

Remp\Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run();
