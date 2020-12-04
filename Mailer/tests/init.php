<?php

require __DIR__ . '/../app/Bootstrap.php';

$configurator = Remp\MailerModule\Bootstrap::boot();
$container = $configurator->createContainer();
