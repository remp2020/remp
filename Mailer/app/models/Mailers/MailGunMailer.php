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
        $this->mailer = Mailgun::create($this->options['api_key']);
    }

    public function send(Message $message)
    {
        $from = null;
        foreach ($message->getFrom() as $email => $name) {
            $from = "$email";
        }

        $to = null;
        foreach ($message->getHeader('To') as $email => $name) {
            $to = $email;
        }

        $attachments = [];
        foreach ($message->getAttachments() as $attachment) {
            preg_match('/(?<filename>\w+\.\w+)/', $attachment->getHeader('Content-Disposition'), $attachmentName);
            $attachments[] = [
                'fileContent' => $attachment->getBody(),
                'filename' => $attachmentName['filename'],
            ];
        }


        $data = [
            'from' => $from,
            'to' => $to,
            'subject' => $message->getSubject(),
            'text' => $message->getBody(),
            'html' => $message->getHtmlBody(),
            'attachment' => $attachments
        ];

        $this->mailer->messages()->send($this->options['domain'], $data);
    }
}
