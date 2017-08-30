<?php

namespace Remp\MailerModule\Mailer;

use Mailgun\Mailgun;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Utils\Json;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Repository\ConfigsRepository;

class MailgunMailer extends Mailer implements IMailer
{
    private $mailer;

    protected $alias = 'remp-mailgun';

    protected $options = [ 'api_key', 'domain' ];

    public function __construct(
        Config $config,
        ConfigsRepository $configsRepository
    ) {
        parent::__construct($config, $configsRepository);
        $this->mailer = Mailgun::create($this->options['api_key']);
    }

    public function send(Message $message)
    {
        $from = null;
        foreach ($message->getFrom() as $email => $name) {
            $from = "$name <$email>";
        }

        $to = null;
        foreach ($message->getHeader('To') as $email => $name) {
            $to = "$name <$email>";
        }

        $attachments = [];
        foreach ($message->getAttachments() as $attachment) {
            preg_match('/(?<filename>\w+\.\w+)/', $attachment->getHeader('Content-Disposition'), $attachmentName);
            $attachments[] = [
                'fileContent' => $attachment->getBody(),
                'filename' => $attachmentName['filename'],
            ];
        }

        $mailVariables = Json::decode($message->getHeader('X-Mailer-Variables'), Json::FORCE_ARRAY);

        $data = [
            'from' => $from,
            'to' => $to,
            'subject' => $message->getSubject(),
            'text' => $message->getBody(),
            'html' => $message->getHtmlBody(),
            'attachment' => $attachments,
            'tag' => $mailVariables['template'],
        ];
        foreach ($mailVariables as $key => $val) {
            $data["v:".$key] = $val;
        }

        $this->mailer->messages()->send($this->options['domain'], $data);
    }
}
