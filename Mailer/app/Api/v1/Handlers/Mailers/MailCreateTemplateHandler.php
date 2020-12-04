<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
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
            new InputParam(InputParam::TYPE_POST, 'mail_layout_id', InputParam::OPTIONAL),
            new InputParam(InputParam::TYPE_POST, 'mail_type_code', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'from', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'subject', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'template_text', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'template_html', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'extras', InputParam::OPTIONAL),
        ];
    }

    public function handle($params)
    {
        // TODO - mail layouts are not identified by code
        if (!$params['mail_layout_id']) {
            // TODO: remove this fallback once internal API's provide the layout themselves
            $params['mail_layout_id'] = 4; // internal fallback
        }

        $mailType = $this->listsRepository->findBy('code', $params['mail_type_code']);

        if (!$mailType) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'mail_type_code not found']);
        }

        $extras = $params['extras'] ?? null;

        if ($extras !== null && !$this->isJson($extras)) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => "'extras' parameter contains invalid JSON value"]);
        }

        $template = $this->templatesRepository->add(
            $params['name'],
            $this->templatesRepository->getUniqueTemplateCode($params['code']),
            $params['description'],
            $params['from'],
            $params['subject'],
            $params['template_text'],
            $params['template_html'],
            $params['mail_layout_id'],
            $mailType->id,
            null,
            $extras
        );

        return new JsonApiResponse(200, ['status' => 'ok', 'id' => $template->id]);
    }

    private function isJson($string)
    {
        try {
            Json::decode($string);
        } catch (JsonException $exception) {
            return false;
        }
        return true;
    }
}
