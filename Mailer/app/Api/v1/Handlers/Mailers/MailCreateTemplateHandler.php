<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

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

    public function params(): array
    {
        return [
            (new PostInputParam('name'))->isRequired(),
            (new PostInputParam('code'))->isRequired(),
            (new PostInputParam('description'))->isRequired(),
            (new PostInputParam('mail_layout_id')),
            (new PostInputParam('mail_type_code'))->isRequired(),
            (new PostInputParam('from'))->isRequired(),
            (new PostInputParam('subject'))->isRequired(),
            (new PostInputParam('template_text'))->isRequired(),
            (new PostInputParam('template_html'))->isRequired(),
            (new PostInputParam('click_tracking')),
            (new PostInputParam('extras')),
        ];
    }

    public function handle(array $params): ResponseInterface
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

        $clickTracking = null;
        if (isset($params['click_tracking'])) {
            $clickTracking = filter_var(
                $params['click_tracking'] ?? null,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
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
            (int) $params['mail_layout_id'],
            $mailType->id,
            $clickTracking,
            $extras
        );

        return new JsonApiResponse(200, [
            'status' => 'ok',
            'id' => $template->id,
            'code' => $template->code,
        ]);
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
