#!/usr/bin/env php
<?php

use Remp\MailerModule\PhinxRegistrator;

$container = require __DIR__ . '/../app/bootstrap.php';
$application = $container->getByType('Symfony\Component\Console\Application');

$phinxRegistrator = new PhinxRegistrator($application, $container->getByType('Remp\MailerModule\EnvironmentConfig'));

$application->run();
