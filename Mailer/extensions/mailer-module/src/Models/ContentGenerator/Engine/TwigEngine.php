<?php

namespace Remp\MailerModule\Models\ContentGenerator\Engine;

use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

class TwigEngine implements IEngine
{
    public function render(string $content, array $params = []): string
    {
        $templates = $params['snippets'] ?? [];
        $templates['index.html'] = $content;
        $loader = new ArrayLoader($templates);
        $twig = new Environment($loader);
        $twig->addExtension(new IntlExtension());

        return $twig->render('index.html', $params);
    }

    public function markSafe(string $content): \Stringable|string
    {
        return new Markup($content, 'UTF-8');
    }
}
