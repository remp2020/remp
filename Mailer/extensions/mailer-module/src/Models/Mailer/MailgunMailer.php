<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Mailer;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Mailgun\HttpClient\HttpClientConfigurator;
use Mailgun\Mailgun;
use Nette\Mail\Message;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Remp\MailerModule\Models\Sender\MailerBatchException;
use Remp\MailerModule\Models\Sender\MailerRuntimeException;
use RuntimeException;

class MailgunMailer extends Mailer
{
    use MailHeaderTrait;

    public const ALIAS = 'remp_mailgun';

    private ?LoggerInterface $logger = null;

    protected array $options = [
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
        ],
        'http_webhook_signing_key' => [
            'required' => false,
            'label' => 'HTTP webhook signing key',
            'description' => "This key is used by Mailgun to sign webhook requests. It can be obtained in Mailgun's dashboard (Sending - Webhooks).",
        ],
    ];

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function createMailer(): Mailgun
    {
        $this->buildConfig(); // fetch newer values

        $clientConfigurator = (new HttpClientConfigurator())
            ->setApiKey($this->option('api_key'));

        if ($endpoint = $this->option('endpoint')) {
            $clientConfigurator->setEndpoint($endpoint);
        }

        if ($this->logger) {
            $stack = HandlerStack::create();
            $stack->push(
                Middleware::log($this->logger, new MessageFormatter(MessageFormatter::DEBUG))
            );

            $client = new Client([
                'handler' => $stack,
            ]);
            $clientConfigurator->setHttpClient($client);
        }

        return new Mailgun($clientConfigurator);
    }

    public function send(Message $mail): void
    {
        $mailer = $this->createMailer();

        $from = null;
        foreach ($mail->getFrom() as $email => $name) {
            $from = "$name <$email>";
        }

        $toHeader = $mail->getHeader('To');
        $recipientVariablesHeaderJson = $mail->getHeader('X-Mailer-Template-Params');
        if (count($toHeader) > 1 && !$recipientVariablesHeaderJson) {
            throw new MailerBatchException("unsafe use of Mailgun mailer with multiple recipients: recipient variables (X-Mailer-Template-Params header) missing");
        }

        $recipientVariablesHeader = Json::decode($recipientVariablesHeaderJson, Json::FORCE_ARRAY);

        $to = [];
        $now = microtime(true);
        $messageIdHeader = null;

        foreach ($toHeader as $email => $name) {
            $messageId = sprintf(
                "remp_mailer_%s_%s@%s",
                hash("crc32c", $email . $now),
                (int) $now,
                $this->option('domain')
            );

            if (count($toHeader) > 1) {
                if (!isset($recipientVariablesHeader[$email])) {
                    throw new MailerBatchException("unsafe use of Mailgun mailer with multiple recipients: recipient variables (X-Mailer-Template-Params header) missing for email: {$email}");
                }
                $messageIdHeader = "%recipient.message_id%";
                $recipientVariablesHeader[$email]['message_id'] = $messageId;
            } else {
                $messageIdHeader = $messageId;
            }
            $to[] = $email;
        }

        $attachments = [];
        foreach ($mail->getAttachments() as $attachment) {
            // example header with attachment: `Content-Disposition: attachment; filename="invoice-2024-09-24.pdf"`
            $filename = $this->getHeaderParameter($attachment->getHeader('Content-Disposition'), 'filename');
            $attachments[] = [
                'fileContent' => $attachment->getBody(),
                'filename' => $filename,
            ];
        }

        $tag = $mail->getHeader('X-Mailer-Tag');
        $clickTracking = $mail->getHeader('X-Mailer-Click-Tracking');
        $listUnsubscribe = $mail->getHeader('List-Unsubscribe');
        $listUnsubscribePost = $mail->getHeader('List-Unsubscribe-Post');

        $data = [
            'from' => $from,
            'to' => $to,
            'subject' => $mail->getSubject(),
            'text' => $mail->getBody(),
            'html' => $mail->getHtmlBody(),
            'attachment' => $attachments,
            'recipient-variables' => Json::encode($recipientVariablesHeader),
            'h:Precedence' => 'bulk', // https://blog.returnpath.com/precedence/
            'h:Message-ID' => $messageIdHeader,
        ];
        if ($tag) {
            $data['o:tag'] = $tag;
        }
        if ($clickTracking !== null) {
            $data['o:tracking-clicks'] = (bool) $clickTracking ? 'yes' : 'no';
        }
        if (isset($listUnsubscribe)) {
            $data['h:List-Unsubscribe'] = $listUnsubscribe;
        }
        if (isset($listUnsubscribe)) {
            $data['h:List-Unsubscribe-Post'] = $listUnsubscribePost;
        }

        $mailVariables = Json::decode($mail->getHeader('X-Mailer-Variables'), Json::FORCE_ARRAY);
        foreach ($mailVariables as $key => $val) {
            $data["v:".$key] = (string) $val;
        }

        try {
            $mailer->messages()->send($this->option('domain'), $data);
        } catch (RuntimeException $exception) {
            throw new MailerRuntimeException($exception->getMessage());
        }
    }

    /**
     * @deprecated Use `createMailer()` method instead.
     */
    public function mailer(): Mailgun
    {
        return $this->createMailer();
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
