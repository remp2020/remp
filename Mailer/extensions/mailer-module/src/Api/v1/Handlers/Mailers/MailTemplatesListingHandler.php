<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Http\IResponse;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\GetInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailTemplatesListingHandler extends BaseHandler
{
    private TemplatesRepository $templatesRepository;

    public function __construct(
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
    }

    public function params(): array
    {
        return [
            (new GetInputParam('codes'))->setMulti(),
            (new GetInputParam('mail_type_codes'))->setMulti(),
            new GetInputParam('with_mail_types'),
            new GetInputParam('page'),
            new GetInputParam('limit'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $results = $this->templatesRepository->all();

        if (isset($params['codes'])) {
            $results->where(['mail_templates.code' => $params['codes']]);
        }

        if (isset($params['mail_type_codes'])) {
            $results->where(['mail_type.code' => $params['mail_type_codes']]);
        }

        if (isset($params['page']) || isset($params['limit'])) {
            $page = (int) ($params['page'] ?? null);
            $limit = (int) ($params['limit'] ?? null);

            if ($page <= 0 || $limit <= 0) {
                return new JsonApiResponse(IResponse::S400_BadRequest, [
                    'status' => 'error',
                    'code' => 'invalid_pagination_params',
                    'message' => sprintf(
                        "Invalid page or limit provided. page: %s, limit: %s",
                        $params['page'] ?? null,
                        $params['limit'] ?? null
                    )
                ]);
            }
            $results->limit($limit, ($page - 1) * $limit);
        }

        $withMailTypes = filter_var($params['with_mail_types'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $result = [];
        foreach ($results as $row) {
            $item = [
                'code' => $row->code,
                'name' => $row->name,
                'description' => $row->description,
                'mail_type_code' => $row->mail_type->code,
                'attachments_enabled' => (bool) $row->attachments_enabled
            ];
            if ($withMailTypes) {
                $item['mail_type'] = [
                    'code' => $row->mail_type->code,
                    'title' => $row->mail_type->title,
                    'description' => $row->mail_type->description,
                    'sorting' => $row->mail_type->sorting,
                ];
            }

            $result[] = $item;
        }

        return new JsonApiResponse(200, $result);
    }
}
