<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Http\IResponse;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailCreateTemplateHandler extends BaseHandler
{
    public const NAME_MAX_LENGTH = 768;

    private $templatesRepository;

    private $listsRepository;

    private $layoutsRepository;

    public function __construct(
        ListsRepository $listsRepository,
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository
    ) {
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
        $this->listsRepository = $listsRepository;
        $this->layoutsRepository = $layoutsRepository;
    }

    public function params(): array
    {
        return [
            (new PostInputParam('name'))->setRequired(),
            (new PostInputParam('code'))->setRequired(),
            (new PostInputParam('description'))->setRequired(),
            (new PostInputParam('mail_layout_id')),
            (new PostInputParam('mail_layout_code')),
            (new PostInputParam('mail_type_code'))->setRequired(),
            (new PostInputParam('from'))->setRequired(),
            (new PostInputParam('subject'))->setRequired(),
            (new PostInputParam('template_text'))->setRequired(),
            (new PostInputParam('template_html'))->setRequired(),
            (new PostInputParam('click_tracking')),
            (new PostInputParam('extras')),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $nameLength = strlen($params['name']);
        if ($nameLength > self::NAME_MAX_LENGTH) {
            return new JsonApiResponse(IResponse::S400_BadRequest, [
                'status' => 'error',
                'code' => 'name_too_long',
                'message' => 'Name length is ' . $nameLength . ' characters, exceeding the max of ' . self::NAME_MAX_LENGTH,
            ]);
        }

        // TODO - mail layouts are not identified by code
        if (!$params['mail_layout_id'] && !$params['mail_layout_code']) {
            // TODO: remove this fallback once internal API's provide the layout themselves
            $params['mail_layout_id'] = 4; // internal fallback
        }

        $mailType = $this->listsRepository->findBy('code', $params['mail_type_code']);
        if (!$mailType) {
            return new JsonApiResponse(IResponse::S400_BadRequest, [
                'status' => 'error',
                'code' => 'mail_type_not_found',
                'message' => 'mail_type_code not found: ' . $params['mail_type_code'],
            ]);
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
            return new JsonApiResponse(IResponse::S400_BadRequest, [
                'status' => 'error',
                'code' => 'invalid_extras',
                'message' => "'extras' parameter contains invalid JSON value",
            ]);
        }

        $mailLayout = null;
        if ($params['mail_layout_id']) {
            $mailLayout = $this->layoutsRepository->find($params['mail_layout_id']);
        } elseif ($params['mail_layout_code']) {
            $mailLayout = $this->layoutsRepository->findBy('code', $params['mail_layout_code']);
        }
        if (!$mailLayout) {
            return new JsonApiResponse(IResponse::S404_NotFound, [
                'status' => 'error',
                'code' => "mail_layout_not_found",
                'message' => "mail layout not found",
            ]);
        }

        $template = $this->templatesRepository->add(
            $params['name'],
            $this->templatesRepository->getUniqueTemplateCode($params['code']),
            $params['description'],
            $params['from'],
            $params['subject'],
            $params['template_text'],
            $params['template_html'],
            $mailLayout->id,
            $mailType->id,
            $clickTracking,
            $extras
        );

        return new JsonApiResponse(IResponse::S200_OK, [
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
