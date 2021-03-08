<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\DateTime;
use Nette\Utils\Arrays;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Api\v1\Handlers\Mailers\Traits\ParseLogFilterConditionsTrait;
use Remp\MailerModule\Repositories\LogsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class LogsHandler extends BaseHandler
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

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/logs.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $logs = $this->logsRepository->allForEmail($payload['email']);
        if (isset($payload['mail_template_ids'])) {
            $logs->where('mail_template_id', $payload['mail_template_ids']);
        }
        if (isset($payload['page'])) {
            $logs->limit($payload['limit'], ($payload['page'] - 1) * $payload['limit']);
        }
        if (isset($payload['limit']) && !isset($payload['page'])) {
            $logs->limit($payload['limit']);
        }

        foreach ($payload['filter'] ?? [] as $key => $filter) {
            foreach ($this->parseConditions($key, $filter) ?? [] as $condition) {
                $logs->where(...$condition);
            }
        }

        $output = [];

        foreach ($logs->order('created_at DESC')->fetchAll() as $log) {
            $item = new \stdClass();
            $item->id = $log->id;
            $item->email = $log->email;
            $item->subject = $log->subject;
            $item->mail_template = [
                'id' => $log->mail_template_id,
                'code' => $log->mail_template->code,
                'name' => $log->mail_template->name,
            ];
            $item->sent_at = $this->formatDate($log->created_at);
            $item->delivered_at = $this->formatDate($log->delivered_at);
            $item->dropped_at = $this->formatDate($log->dropped_at);
            $item->spam_complained_at = $this->formatDate($log->spam_complained_at);
            $item->hard_bounced_at = $this->formatDate($log->hard_bounced_at);
            $item->clicked_at = $this->formatDate($log->clicked_at);
            $item->opened_at = $this->formatDate($log->opened_at);
            $item->attachment_size = $log->attachment_size;
            $output[] = $item;
        }

        return new JsonApiResponse(200, $output);
    }

    private function formatDate($date)
    {
        return is_null($date) ? null : $date->format(DateTime::RFC3339);
    }
}
