<?php

namespace Remp\MailerModule\ContentGenerator;

use Remp\MailerModule\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\ContentGenerator\Replace\IReplace;
use Nette\Database\IRow;
use Nette\Utils\Strings;

class ContentGenerator
{
    private $engineFactory;

    private $time;

    /** @var IReplace[] */
    private $replaceList = [];

    public function __construct(EngineFactory $engineFactory)
    {
        $this->engineFactory = $engineFactory;

        $this->time = new \DateTime();
    }

    public function register(IReplace $replace)
    {
        $this->replaceList[] = $replace;
    }

    public function render(GeneratorInput $generatorInput): MailContent
    {
        $htmlBody = $this->generate($generatorInput->template()->mail_body_html, $generatorInput->params());
        $html = $this->wrapLayout($generatorInput->template(), $htmlBody, $generatorInput->layout()->layout_html, $generatorInput->params());

        $textBody = $this->generate($generatorInput->template()->mail_body_text, $generatorInput->params());
        $text = $this->wrapLayout($generatorInput->template(), $textBody, $generatorInput->layout()->layout_text, $generatorInput->params());

        foreach ($this->replaceList as $replace) {
            $html = $replace->replace($html, $generatorInput);
            $text = $replace->replace($text, $generatorInput);
        }

        return new MailContent($html, $text);
    }

    public function getEmailParams(GeneratorInput $generatorInput, array $emailParams)
    {
        $outputParams = [];

        foreach ($emailParams as $name => $value) {
            foreach ($this->replaceList as $replace) {
                $value = $replace->replace($value, $generatorInput);
            }
            $outputParams[$name] = $value;
        }

        return $outputParams;
    }

    private function generate(string $bodyTemplate, array $params)
    {
        $params['time'] = $this->time;

        return $this->engineFactory->engine()->render($bodyTemplate, $params);
    }

    private function wrapLayout(IRow $template, $renderedTemplateContent, $layoutContent, $params)
    {
        if (!$layoutContent) {
            return $renderedTemplateContent;
        }
        $layoutParams = [
            'title' => $template->subject,
            'content' => $renderedTemplateContent,
            'time' => $this->time,
        ];
        $params = array_merge($layoutParams, $params);
        return $this->engineFactory->engine()->render($layoutContent, $params);
    }
}
