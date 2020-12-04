<?php

namespace Remp\MailerModule\Models\ContentGenerator\Engine;

use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigEngine implements IEngine
{
    public function render(string $content, array $params = []): string
    {
        $loader = new ArrayLoader([
            'index.html' => $content,
        ]);

        $twig = new Environment($loader);

        return $twig->render('index.html', $params);
    }
}
