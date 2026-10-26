<?php

namespace Remp\MailerModule\Models\ContentGenerator\Engine;

use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

class TwigEngine implements IEngine
{
    /** @var ExtensionInterface[] */
    private array $extensions = [];

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[] = $extension;
    }

    public function render(string $content, array $params = []): string
    {
        $templates = $params['snippets'] ?? [];
        $templates['index.html'] = $content;
        $loader = new ArrayLoader($templates);
        $twig = new Environment($loader);
        $twig->addExtension(new IntlExtension());
        foreach ($this->extensions as $extension) {
            $twig->addExtension($extension);
        }

        return $twig->render('index.html', $params);
    }

    public function markSafe(string $content): \Stringable|string
    {
        return new Markup($content, 'UTF-8');
    }
}
