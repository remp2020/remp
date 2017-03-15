#!/usr/bin/env php
<?php

use Remp\MailerModule\PhinxRegistrator;
use Symfony\Component\Console\Application;
use Nette\Database\DriverException;
use Nette\InvalidArgumentException;

$container = require __DIR__ . '/../app/bootstrap.php';
$application = new Application();
$application->setCatchExceptions(false);

$phinxRegistrator = new PhinxRegistrator($application);

$application->run();
