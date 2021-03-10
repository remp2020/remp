<?php

namespace Remp\MailerModule\Models\ContentGenerator\Engine;

use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigEngine implements IEngine
{
    public function render(string $content, array $params = []): string
    {
        $templates = $params['snippets'] ?? [];
        $templates['index.html'] = $content;
        $loader = new ArrayLoader($templates);
        $twig = new Environment($loader);

        return $twig->render('index.html', $params);
    }
}
