<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Repository\TemplatesRepository;
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
        ];
    }

    public function handle($params)
    {
        $results = $this->templatesRepository->all();

        if (isset($params['mail_type_codes'])) {
            $results->where(['mail_type.code' => $params['mail_type_codes']]);
        }

        $result = [];
        foreach ($results as $row) {
            $result[] = [
                'code' => $row->code,
                'name' => $row->name,
                'description' => $row->description,
                'mail_type_code' => $row->mail_type->code,
            ];
        }

        return new JsonApiResponse(200, $result);
    }
}
