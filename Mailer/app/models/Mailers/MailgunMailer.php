<?php

namespace Remp\MailerModule\Mailer;

use Mailgun\Mailgun;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Utils\Json;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Repository\ConfigsRepository;
use Remp\MailerModule\Sender\MailerBatchException;

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

        $toHeader = $message->getHeader('To');
        $recipientVariablesHeaderJson = $message->getHeader('X-Mailer-Template-Params');
        if (count($toHeader) > 1 && !$recipientVariablesHeaderJson) {
            throw new MailerBatchException("unsafe use of Mailgun mailer with multiple recipients: recipient variables (X-Mailer-Template-Params header) missing");
        }

        $recipientVariablesHeader = Json::decode($recipientVariablesHeaderJson, Json::FORCE_ARRAY);
        $to = [];
        foreach ($toHeader as $email => $name) {
            if (count($toHeader) > 1 && !isset($recipientVariablesHeader[$email])) {
                throw new MailerBatchException("unsafe use of Mailgun mailer with multiple recipients: recipient variables (X-Mailer-Template-Params header) missing for email: {$email}");
            }
            $to[] = $email;
        }

        $attachments = [];
        foreach ($message->getAttachments() as $attachment) {
            preg_match('/(?<filename>\w+\.\w+)/', $attachment->getHeader('Content-Disposition'), $attachmentName);
            $attachments[] = [
                'fileContent' => $attachment->getBody(),
                'filename' => $attachmentName['filename'],
            ];
        }

        $tag = $message->getHeader('X-Mailer-Tag');

        $data = [
            'from' => $from,
            'to' => $to,
            'subject' => $message->getSubject(),
            'text' => $message->getBody(),
            'html' => $message->getHtmlBody(),
            'attachment' => $attachments,
            'recipient-variables' => $message->getHeader('X-Mailer-Template-Params'),
        ];
        if ($tag) {
            $data['o:tag'] = $tag;
        }

        $mailVariables = Json::decode($message->getHeader('X-Mailer-Variables'), Json::FORCE_ARRAY);
        foreach ($mailVariables as $key => $val) {
            $data["v:".$key] = $val;
        }

        $this->mailer->messages()->send($this->options['domain'], $data);
    }

    public function mailer()
    {
        return $this->mailer;
    }

    public function option($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    public function transformTemplateParams(array $params)
    {
        $transformed = [];
        foreach ($params as $key => $value) {
            $prefix = '';
            if ($value[0] === '?') {
                $prefix = '?';
                $params[$key] = substr($value, 1);
            }
            $transformed[$key] = "{$prefix}%recipient.{$key}%";
        }
        return [$transformed, $params];
    }

    public function supportsBatch(): bool
    {
        return true;
    }
}
