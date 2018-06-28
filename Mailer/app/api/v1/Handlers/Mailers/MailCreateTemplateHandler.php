<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailCreateTemplateHandler extends BaseHandler
{
    private $templatesRepository;

    private $listsRepository;

    public function __construct(
        ListsRepository $listsRepository,
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
        $this->listsRepository = $listsRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'name', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'code', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'description', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'from', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'subject', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'template_text', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'template_html', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'mail_type_code', InputParam::REQUIRED),
        ];
    }

    public function handle($params)
    {
        // TODO - mail layouts are not identified by code
        $layoutId = 4; // empty layout

        $mailType = $this->listsRepository->findBy('code', $params['mail_type_code']);

        if (!$mailType) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'mail_type_code not found']);
        }

        $template = $this->templatesRepository->add(
            $params['name'],
            $this->templatesRepository->getUniqueTemplateCode($params['code']),
            $params['description'],
            $params['from'],
            $params['subject'],
            $params['template_text'],
            $params['template_html'],
            $layoutId,
            $mailType->id
        );

        return new JsonApiResponse(200, ['status' => 'ok', 'id' => $template->id]);
    }
}
