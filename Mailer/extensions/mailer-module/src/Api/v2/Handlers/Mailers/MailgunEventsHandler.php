<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v2\Handlers\Mailers;

use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Hermes\RedisDriver;
use Remp\MailerModule\Models\Mailer\MailgunMailer;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Models\Sender\MailerNotExistsException;
use Tomaj\Hermes\Emitter;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\GetInputParam;
use Tomaj\NetteApi\Params\JsonInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

/**
 * MailgunEventsHandler implements updated version of Mailgun Webhooks using JSON payload instead of POST params.
 *
 * {
 *   "signature":
 *   {
 *     "timestamp": "1529006854",
 *     "token": "a8ce0edb2dd8301dee6c2405235584e45aa91d1e9f979f3de0",
 *     "signature": "d2271d12299f6592d9d44cd9d250f0704e4674c30d79d07c47a66f95ce71cf55"
 *   },
 *   "event-data":
 *   {
 *     "event": "opened",
 *     "timestamp": 1529006854.329574,
 *     "id": "DACSsAdVSeGpLid7TN03WA",
 *     // ...
 *   }
 * }
 *
 * Class MailgunEventsHandler
 * @package Remp\MailerModule\Api\v2\Handlers\Mailers
 */
class MailgunEventsHandler extends BaseHandler
{

    public function __construct(private readonly MailerFactory $mailerFactory, private readonly Emitter $emitter)
    {
        parent::__construct();
    }

    public function params(): array
    {
        return [
            new GetInputParam('code'),
            (new JsonInputParam('data', file_get_contents(__DIR__ . '/mailgun-webhook.schema.json')))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $data = $params['data'];
        try {
            $mailer = $this->mailerFactory->getMailerByAliasAndCode(MailgunMailer::ALIAS, $params['code']);
        } catch (MailerNotExistsException $e) {
            return new JsonApiResponse(403, [
                'status' => 'error',
                'code' => 'invalid_mailgun_mailer_code',
                'message' => "Mailgun mailer with code '{$params['code']}' doesn't exist.",
            ]);
        }

        $signKey = $mailer->getConfig('http_webhook_signing_key');
        if (!$signKey) {
            // Fallback to the API key that was used as a signing key in the past.
            $signKey = $mailer->getConfig('api_key');
        }
        if (hash_hmac('sha256', $data['signature']['timestamp'] . $data['signature']['token'], $signKey) !== $data['signature']['signature']) {
            return new JsonApiResponse(403, ['status' => 'error', 'message' => 'Wrong signature.']);
        }

        $this->emitter->emit(new HermesMessage('mailgun-event', [
            'mail_sender_id' => $data['event-data']['user-variables']['mail_sender_id'] ?? null,
            'timestamp' => $data['event-data']['timestamp'],
            'event' => $data['event-data']['event'],
            'email' => $data['event-data']['recipient'],
            'reason' => $data['event-data']['reason'] ?? null,
            'severity' => $data['event-data']['severity'] ?? null,
            'client' => $data['event-data']['client-info'] ?? null,
            'url' => $data['event-data']['url'] ?? null,
        ]), RedisDriver::PRIORITY_LOW);

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
