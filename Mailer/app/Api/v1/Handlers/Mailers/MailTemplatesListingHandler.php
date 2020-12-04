<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTemplatesListingHandler extends BaseHandler
{
    private $templatesRepository;

    public function __construct(
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'mail_type_codes', InputParam::OPTIONAL, null, true),
            new InputParam(InputParam::TYPE_GET, 'with_mail_types', InputParam::OPTIONAL),
        ];
    }

    public function handle($params)
    {
        $results = $this->templatesRepository->all();

        if (isset($params['mail_type_codes'])) {
            $results->where(['mail_type.code' => $params['mail_type_codes']]);
        }

        $withMailTypes = filter_var($params['with_mail_types'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $result = [];
        foreach ($results as $row) {
            $item = [
                'code' => $row->code,
                'name' => $row->name,
                'description' => $row->description,
                'mail_type_code' => $row->mail_type->code,
            ];
            if ($withMailTypes) {
                $item['mail_type'] = [
                    'code' => $row->mail_type->code,
                    'title' => $row->mail_type->title,
                    'description' => $row->mail_type->description,
                    'sorting' => $row->mail_type->sorting,
                ];
            }

            $result[] =  $item;
        }

        return new JsonApiResponse(200, $result);
    }
}
