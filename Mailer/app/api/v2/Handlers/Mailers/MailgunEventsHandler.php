<?php

namespace Remp\MailerModule\Api\v2\Handlers\Mailers;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Sender;
use Tomaj\Hermes\Emitter;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;

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
    private $sender;

    private $emitter;

    public function __construct(Sender $sender, Emitter $emitter)
    {
        parent::__construct();
        $this->sender = $sender;
        $this->emitter = $emitter;
    }

    public function handle($params)
    {
        $json = file_get_contents("php://input");
        if (empty($json)) {
            $response = new JsonApiResponse(400, ['status' => 'error', 'message' => 'Empty request']);
            return $response;
        }

        try {
            $params = Json::decode($json, Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            $response = new JsonApiResponse(400, ['status' => 'error', 'message' => 'Invalid JSON payload: ' . $e->getMessage()]);
            return $response;
        }

        $mailer = $this->sender->getMailer('remp-mailgun');

        if (hash_hmac('sha256', $params['signature']['timestamp'] . $params['signature']['token'], $mailer->getConfig('api_key')) !== $params['signature']['signature']) {
            return new JsonApiResponse(403, ['status' => 'error', 'message' => 'Wrong signature.']);
        }

        $this->emitter->emit(new HermesMessage('mailgun-event', [
            'mail_sender_id' => $params['event-data']['user-variables']['mail_sender_id'] ?? null,
            'timestamp' => $params['event-data']['timestamp'],
            'event' => $params['event-data']['event'],
            'reason' => $params['event-data']['reason'] ?? null,
        ]));

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
