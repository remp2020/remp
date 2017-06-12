<?php

namespace Remp\MailerModule\Mailer;

use Mailgun\Mailgun;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Repository\ConfigsRepository;

class MailGunMailer extends Mailer implements IMailer
{
    private $mailer;

    protected $options = [ 'api_key', 'domain' ];

    public function __construct(
        Config $config,
        ConfigsRepository $configsRepository
    )
    {
        parent::__construct($config, $configsRepository);
        $this->mailer = new Mailgun($this->options['api_key']);
    }

    public function send(Message $mail)
    {
        $from = null;
        foreach ($mail->getFrom() as $email => $name) {
            $from = "$name <$email>";
        }

        $to = null;
        foreach ($mail->getHeader('To') as $email => $name) {
            $to = $email;
        }

        $attachments = [];
        foreach ($mail->getAttachments() as $attachment) {
            $attachments[] = [
                'fileContent' => $attachment->getBody(),
                'filename' => 'attachment' . microtime(),
            ];
        }


        $data = [
            'from' => $from,
            'to' => $to,
            'subject' => $mail->getSubject(),
            'text' => $mail->getBody(),
            'html' => $mail->getHtmlBody(),
            'attachment' => $attachments
        ];

        $this->mailer->messages()->send($this->options['domain'], $data);
    }
}
