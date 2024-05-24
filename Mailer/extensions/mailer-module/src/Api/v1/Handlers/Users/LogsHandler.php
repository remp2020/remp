<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Api\v1\Handlers\Mailers\Traits\ParseLogFilterConditionsTrait;
use Remp\MailerModule\Repositories\LogsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\JsonInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;
use Tracy\Debugger;
use Tracy\ILogger;

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
            new JsonInputParam('json', file_get_contents(__DIR__ . '/logs.schema.json')),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $params['json'];
        $logs = $this->logsRepository->getTable();
        if (isset($payload['user_id'])) {
            $logs->where('user_id', $payload['user_id']);
        } else {
            $logs->where('email', $payload['email']);
        }

        if (isset($payload['mail_template_ids'])) {
            $logs->where('mail_template_id', $payload['mail_template_ids']);
        }
        if (isset($payload['mail_template_codes'])) {
            $logs->where('mail_template.code', $payload['mail_template_codes']);
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

        try {
            $logs = $logs->order('created_at DESC')->fetchAll();
        } catch (\Exception $exception) {
            if ($exception->getCode() === '42S22' && str_contains($exception->getMessage(), 'user_id')) {
                Debugger::log("Missing 'user_id' column in 'mail_logs' table", ILogger::WARNING);
                return new JsonApiResponse(IResponse::S501_NotImplemented, [
                    'status' => 'error',
                    'message' => "Missing 'user_id' column in 'mail_logs' table",
                    'detail' => "You probably don't have newest version of 'mail_logs' table. For more information about migration, check 'mail:migrate-mail-logs-and-conversions' command."
                ]);
            }
            throw $exception;
        }

        foreach ($logs as $log) {
            $item = new \stdClass();
            $item->id = $log->id;
            $item->email = $log->email;
            $item->user_id = $log->user_id ?? null;
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
