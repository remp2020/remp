<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Hermes\RedisDriver;
use Remp\MailerModule\Models\Mailer\MailgunMailer;
use Remp\MailerModule\Models\Sender;
use Tomaj\Hermes\Emitter;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

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

    public function params(): array
    {
        return [
            (new PostInputParam('mail_sender_id'))->setRequired(),
            (new PostInputParam('timestamp'))->setRequired(),
            (new PostInputParam('token'))->setRequired(),
            (new PostInputParam('signature'))->setRequired(),
            (new PostInputParam('recipient'))->setRequired(),
            (new PostInputParam('event'))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $mailer = $this->sender->getMailer(MailgunMailer::ALIAS);

        if (hash_hmac('sha256', $params['timestamp'] . $params['token'], $mailer->getConfig('api_key')) !== $params['signature']) {
            return new JsonApiResponse(403, ['status' => 'error', 'message' => 'Wrong signature.']);
        }

        $this->emitter->emit(new HermesMessage('mailgun-event', [
            'mail_sender_id' => $params['mail_sender_id'],
            'timestamp' => $params['timestamp'],
            'event' => $params['event'],
            'reason' => null,
        ]), RedisDriver::PRIORITY_LOW);

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
