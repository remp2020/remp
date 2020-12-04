<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Mailer;

use Mailgun\Mailgun;
use Nette\Mail\Message;
use Nette\Utils\Json;
use Remp\MailerModule\Models\Sender\MailerBatchException;

class MailgunMailer extends Mailer
{
    private $mailer;

    protected $alias = 'remp-mailgun';

    protected $options = [
        'api_key' => [
            'required' => true,
            'label' => 'Mailgun API key',
            'description' => 'Mailgun domain (e.g. key-abcdefgh12345678)',
        ],
        'domain' => [
            'required' => true,
            'label' => 'Mailgun domain',
            'description' => 'Mailgun domain (e.g. mg.example.com)',
        ],
        'endpoint' => [
            'required' => false,
            'label' => 'Mailgun endpoint',
            'description' => 'Mailgun server URL (e.g. https://api.mailgun.net)',
        ]
    ];

    private function initMailer()
    {
        if ($this->mailer) {
            return $this->mailer;
        }

        if ($endpoint = $this->option('endpoint')) {
            $this->mailer = Mailgun::create($this->option('api_key'), $endpoint);
        } else {
            $this->mailer = Mailgun::create($this->option('api_key'));
        }
    }

    public function send(Message $message): void
    {
        $this->initMailer();

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

        $clickTracking = null;
        if ($message->getHeader('X-Mailer-Click-Tracking') !== null) {
            $clickTracking = $message->getHeader('X-Mailer-Click-Tracking');
        }

        $data = [
            'from' => $from,
            'to' => $to,
            'subject' => $message->getSubject(),
            'text' => $message->getBody(),
            'html' => $message->getHtmlBody(),
            'attachment' => $attachments,
            'recipient-variables' => $message->getHeader('X-Mailer-Template-Params'),
            'h:Precedence' => 'bulk', // https://blog.returnpath.com/precedence/
        ];
        if ($tag) {
            $data['o:tag'] = $tag;
        }
        if ($clickTracking !== null) {
            $data['o:tracking-clicks'] = (bool) $clickTracking ? 'yes' : 'no';
        }

        $mailVariables = Json::decode($message->getHeader('X-Mailer-Variables'), Json::FORCE_ARRAY);
        foreach ($mailVariables as $key => $val) {
            $data["v:".$key] = (string) $val;
        }

        $this->mailer->messages()->send($this->option('domain'), $data);
    }

    public function mailer(): Mailgun
    {
        $this->initMailer();
        return $this->mailer;
    }

    public function option(string $key): ?string
    {
        return $this->options[$key]['value'] ?? null;
    }

    public function transformTemplateParams(array $params): array
    {
        $transformed = [];
        foreach ($params as $key => $value) {
            $prefix = '';
            $value = (string) $value;
            if ($value !== '' && $value[0] === '?') {
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
