<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Repository\LogEventsRepository;
use Nette\Utils\DateTime;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Repository\LogsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailgunHandler extends BaseHandler
{
    private $apiKey;

    /** @var LogsRepository */
    private $logsRepository;

    /** @var LogEventsRepository */
    private $logEventsRepository;

    public function __construct(
        Config $config,
        logsRepository $logsRepository,
        LogEventsRepository $logEventsRepository
    ) {
        parent::__construct();
        $this->apiKey = $config->get('mailgun_api_key');
        $this->logsRepository = $logsRepository;
        $this->logEventsRepository = $logEventsRepository;
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
        if (hash_hmac('sha256', $params['timestamp'] . $params['token'], $this->mailgunApiKey) !== $params['signature']) {
            return new JsonApiResponse(403, ['status' => 'error', 'message' => 'Wrong signature.']);
        }

        $log = $this->logsRepository->findBySenderId($params['mail_sender_id']);
        if (!$log) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'Message not found.']);
        }

        $date = DateTime::from($params['timestamp']);

        $updateMapping = [
            'delivered' => 'delivered_at',
            'clicked' => 'clicked_at',
            'opened' => 'opened_at',
            'complained' => 'spam_complained_at',
            'bounced' => 'hard_bounced_at',
            'dropped' => 'dropped_at',
        ];

        if (isset($updateMapping[$params['event']])) {
            $this->logsRepository->update($log, [
                $updateMapping[$params['event']] => $date,
                'updated_at' => new DateTime(),
            ]);
        } else {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Unknown event.']);
        }

        $this->logEventsRepository->addLog($log, $date, $params['event']);

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
