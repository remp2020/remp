<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Utils\DateTime;
use Nette\Utils\Arrays;
use Remp\MailerModule\ActiveRow;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Api\v1\Handlers\Mailers\Traits\ParseLogFilterConditionsTrait;
use Remp\MailerModule\Repository\LogsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailLogsForEmailHandler extends BaseHandler
{
    private $logsRepository;

    use JsonValidationTrait;
    use ParseLogFilterConditionsTrait;

    public function __construct(
        LogsRepository $logsRepository
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST_RAW, 'raw')
        ];
    }

    public function handle($params)
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/logs.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $output = $this->logsRepository->allForEmail($payload['email']);

        if (isset($payload['filter'])) {
            $output->where($this->parseConditions($payload['filter']));
        }

        if (isset($payload['mail_template_ids'])) {
            $output->where('mail_template_id', $payload['mail_template_ids']);
        }

        if (isset($payload['page'])) {
            $output->limit($payload['limit'], ($payload['page'] - 1) * $payload['limit']);
        }

        if (isset($payload['limit']) && !isset($payload['page'])) {
            $output->limit($payload['limit']);
        }

        $output = Arrays::map($output->order('created_at DESC')->fetchAll(), function (ActiveRow $row) {
            return [
                'id' => $row->id,
                'email' => $row->email,
                'subject' => $row->subject,
                'mail_template_id' => $row->mail_template_id,
                'delivered_at' => $this->formatDate($row->delivered_at),
                'dropped_at' => $this->formatDate($row->dropped_at),
                'spam_complained_at' => $this->formatDate($row->spam_complained_at),
                'hard_bounced_at' => $this->formatDate($row->hard_bounced_at),
                'clicked_at' => $this->formatDate($row->clicked_at),
                'opened_at' => $this->formatDate($row->opened_at),
                'attachment_size' => $row->attachment_size,
                'created_at' => $this->formatDate($row->created_at),
            ];
        });

        return new JsonApiResponse(200, $output);
    }

    private function formatDate($date)
    {
        return is_null($date) ? null : $date->format(DateTime::RFC3339);
    }
}
