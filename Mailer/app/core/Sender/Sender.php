<?php

namespace Remp\MailerModule;

use Nette\Database\IRow;
use Nette\Mail\Message;
use Remp\MailerModule\Sender\ContentGenerator;
use Remp\MailerModule\Sender\MailerFactory;

class Sender
{
    /** @var array */
    private $recipient;

    /** @var IRow */
    private $template;

    /** @var  array */
    private $params = [];

    /** @var  array */
    private $attachments = [];

    /** @var MailerFactory */
    private $mailerFactory;

    public function __construct(MailerFactory $mailerFactory)
    {
        $this->mailerFactory = $mailerFactory;
    }

    public function setRecipient($email, $name = null)
    {
        $this->recipient = [
            'email' => $email,
            'name' => $name,
        ];

        return $this;
    }

    public function addAttachment($name, $content = null)
    {
        $this->attachments[$name] = $content;

        return $this;
    }

    public function setTemplate(IRow $template)
    {
        $this->template = $template;

        return $this;
    }

    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    public function send()
    {
        $message = new Message();
        $message->addTo($this->recipient['email'], $this->recipient['name']);
        $message->setFrom($this->template->from);
        $message->setSubject($this->template->subject);

        $generator = new ContentGenerator($this->template, $this->template->layout);
        if ($this->template->mail_body_text) {
            $message->setBody($generator->getTextBody($this->params));
        }

        if ($this->template->mail_body_html) {
            $message->setHtmlBody($generator->getHtmlBody($this->params));
        }

        foreach ($this->attachments as $name => $content) {
            $message->addAttachment($name, $content);
        }

        $this->mailerFactory->getMailer()->send($message);
    }
}
