<?php

namespace Remp\MailerModule\Sender;

use Nette\Database\IRow;
use Remp\MailerModule\Replace\UtmReplace;
use Twig_Environment;
use Twig_Loader_Array;

class ContentGenerator
{
    /** @var  IRow */
    private $template;

    /** @var  IRow */
    private $layout;

    private $utmReplace;

    public function __construct(IRow $template, IRow $layout)
    {
        $this->template = $template;
        $this->layout = $layout;

        $this->utmReplace = new UtmReplace($template->mail_type->code, 'email', $template->code);
    }

    public function getHtmlBody($params)
    {
        $bodyMessageText = $this->generateBody($this->template->mail_body_html, $params);
        $mail = $this->wrapLayout($this->template->subject, $bodyMessageText, $this->layout->layout_html, $params);
        $mail = $this->utmReplace->replace($mail);
        return $mail;
    }

    public function getTextBody($params)
    {
        $bodyMessageText = $this->generateBody($this->template->mail_body_text, $params);
        $mail = $this->wrapLayout($this->template->subject, $bodyMessageText, $this->layout->layout_text, $params);
        $mail = $this->utmReplace->replace($mail);
        return $mail;
    }

    private function generateBody($bodyTemplate, $params)
    {
        $loader = new Twig_Loader_Array([
            'my_template' => $bodyTemplate,
        ]);
        $twig = new Twig_Environment($loader);
        $bodyTemplate = $twig->render('my_template', $params);
        return $bodyTemplate;
    }

    private function wrapLayout($subject, $renderedTemplateContent, $layoutContent, $params)
    {
        if (!$layoutContent) {
            return $renderedTemplateContent;
        }
        $loader = new Twig_Loader_Array([
            'my_template' => $layoutContent,
        ]);
        $twig = new Twig_Environment($loader);

        $layoutParams = [
            'title' => $subject,
            'content' => $renderedTemplateContent,
        ];
        $params = array_merge($layoutParams, $params);
        $content = $twig->render('my_template', $params);
        return $content;
    }
}
