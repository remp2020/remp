<?php

namespace Remp\MailerModule\ContentGenerator;

use Remp\MailerModule\Replace\UtmReplace;
use Nette\Database\IRow;
use Twig_Environment;
use Twig_Loader_Array;

class ContentGenerator
{

    private $mailTemplate;

    private $mailLayout;

    private $links;

    private $time;

    private $utmReplace;

    public function __construct(IRow $mailTemplate, IRow $mailLayout, array $links = [])
    {
        $this->mailTemplate = $mailTemplate;
        $this->mailLayout = $mailLayout;
        $this->links = $links;
        $this->time = new \DateTime();

        $this->utmReplace = new UtmReplace($mailTemplate->mail_type->code, 'email', $mailTemplate->code);
    }

    public function getHtmlBody($params)
    {
        $bodyMessageText = $this->generateBody($this->mailTemplate->mail_body_html, $params);
        $mail = $this->wrapLayout($this->mailTemplate, $bodyMessageText, $this->mailLayout->layout_html, $params);
        $mail = $this->utmReplace->replace($mail);
        return $mail;
    }

    public function getTextBody($params)
    {
        $bodyMessageText = $this->generateBody($this->mailTemplate->mail_body_text, $params);
        $mail = $this->wrapLayout($this->mailTemplate, $bodyMessageText, $this->mailLayout->layout_text, $params);
        $mail = $this->utmReplace->replace($mail);
        return $mail;
    }

    private function generateBody($bodyTemplate, $params)
    {
        $loader = new Twig_Loader_Array([
            'my_template' => $bodyTemplate,
        ]);
        $twig = new Twig_Environment($loader);
        $params['links'] = $this->links;
        $params['time'] = $this->time;
        $bodyTemplate = $twig->render('my_template', $params);
        return $bodyTemplate;
    }

    private function wrapLayout(IRow $template, $renderedTemplateContent, $layoutContent, $params)
    {
        if (!$layoutContent) {
            return $renderedTemplateContent;
        }
        $loader = new Twig_Loader_Array([
            'my_template' => $layoutContent,
        ]);
        $twig = new Twig_Environment($loader);

        $layoutParams = [
            'title' => $template->subject,
            'content' => $renderedTemplateContent,
            'links' => $this->links,
            'time' => $this->time,
        ];
        $params = array_merge($layoutParams, $params);
        $content = $twig->render('my_template', $params);
        return $content;
    }
}
