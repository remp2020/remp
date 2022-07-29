<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

echo '
Latte linter
------------
';

if ($argc < 2) {
    echo "Usage: latte-lint <path>\n";
    exit(1);
}

$engine = new Latte\Engine;

Nette\Bridges\ApplicationLatte\UIMacros::install($engine->getCompiler());
Nette\Bridges\FormsLatte\FormMacros::install($engine->getCompiler());
\Remp\MailerModule\Latte\PermissionMacros::install($engine->getCompiler());
\Oops\WebpackNetteAdapter\Latte\WebpackMacros::install($engine->getCompiler());

$debug = in_array('--debug', $argv, true);
$path = $argv[1];
$linter = new Latte\Tools\Linter($engine, $debug);
$ok = $linter->scanDirectory($path);
exit($ok ? 0 : 1);