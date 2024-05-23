<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ContentGenerator;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\ContentGenerator\Replace\IReplace;
use Remp\MailerModule\Models\MailTranslator;

class ContentGenerator
{
    /** @var IReplace[] */
    private array $replaceList = [];

    public function __construct(
        private readonly EngineFactory $engineFactory,
        private readonly MailTranslator $mailTranslator,
    ) {
    }

    public function register(IReplace $replace): void
    {
        $this->replaceList[] = $replace;
    }

    public function render(GeneratorInput $generatorInput, array $context = null): MailContent
    {
        $params = $generatorInput->params();

        $template = $this->mailTranslator->translateTemplate($generatorInput->template(), $generatorInput->locale());
        $layout = $this->mailTranslator->translateLayout($generatorInput->layout(), $generatorInput->locale());

        if (isset($params['snippets'])) {
            $params['snippets'] = $this->mailTranslator->translateSnippets($params['snippets'], $generatorInput->locale());
        }

        $htmlBody = $this->generate($template->getHtmlBody(), $params);
        $html = $this->wrapLayout($template->getSubject(), $htmlBody, $layout->getHtml(), $params);

        // replace HTML snippets with their text versions
        if (isset($params['snippets']) && isset($params['snippets_text'])) {
            $params['snippets'] = $this->mailTranslator->translateSnippets($params['snippets_text'], $generatorInput->locale());
        }

        $textBody = $this->generate($template->getTextBody(), $params);
        $text = $this->wrapLayout($template->getSubject(), $textBody, $layout->getText(), $params);

        $subject = $this->generate($template->getSubject(), $params);

        foreach ($this->replaceList as $replace) {
            $html = $replace->replace($html, $generatorInput, $context);
            $text = $replace->replace($text, $generatorInput, $context);
        }

        return new MailContent($html, $text, $subject, $template->getFrom());
    }

    public function getEmailParams(GeneratorInput $generatorInput, array $emailParams, array $context = null): array
    {
        $outputParams = [];

        foreach ($emailParams as $name => $value) {
            if (!is_string($value)) {
                $outputParams[$name] = $value;
                continue;
            }

            foreach ($this->replaceList as $replace) {
                $value = $replace->replace($value, $generatorInput, $context);
            }
            $outputParams[$name] = $value;
        }

        return $outputParams;
    }

    private function generate(string $content, array $params): string
    {
        $params['time'] = new DateTime();

        return $this->engineFactory->engine()->render($content, $params);
    }

    private function wrapLayout(string $subject, string $renderedTemplateContent, string $layoutContent, array $params): string
    {
        if (!$layoutContent) {
            return $renderedTemplateContent;
        }
        $layoutParams = [
            'title' => $subject,
            'content' => $renderedTemplateContent,
            'time' => new DateTime(),
        ];
        $params = array_merge($layoutParams, $params);
        return $this->engineFactory->engine()->render($layoutContent, $params);
    }
}
