<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Sender;
use Tomaj\Hermes\Emitter;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

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

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'mail_sender_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'timestamp', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'token', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'signature', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'recipient', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'event', InputParam::REQUIRED),
        ];
    }

    public function handle($params)
    {
        $mailer = $this->sender->getMailer('remp-mailgun');

        if (hash_hmac('sha256', $params['timestamp'] . $params['token'], $mailer->getConfig('api_key')) !== $params['signature']) {
            return new JsonApiResponse(403, ['status' => 'error', 'message' => 'Wrong signature.']);
        }

        $this->emitter->emit(new HermesMessage('mailgun-event', [
            'mail_sender_id' => $params['mail_sender_id'],
            'timestamp' => $params['timestamp'],
            'event' => $params['event'],
            'reason' => null,
        ]));

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
