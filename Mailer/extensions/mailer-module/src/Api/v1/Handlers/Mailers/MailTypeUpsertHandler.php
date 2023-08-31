<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailTypeUpsertHandler extends BaseHandler
{
    private $listsRepository;

    private $listCategoriesRepository;

    private $templatesRepository;

    public function __construct(
        ListsRepository $listsRepository,
        ListCategoriesRepository $listCategoriesRepository,
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();

        $this->listsRepository = $listsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
        $this->templatesRepository = $templatesRepository;
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        try {
            $data = Json::decode($params['raw'], Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'input data was not valid JSON']);
        }

        if (isset($data['mail_type_category_id'])) {
            $listCategory = $this->listCategoriesRepository->find($data['mail_type_category_id']);
            if (!$listCategory) {
                return new JsonApiResponse(404, [
                    'status' => 'error',
                    'code' => 'list_category_not_found',
                    'message' => 'Invalid mail_type_category_id provided: ' . $data['mail_type_category_id'],
                ]);
            }
        }

        if (array_key_exists('subscribe_mail_template_code', $data)) {
            if ($data['subscribe_mail_template_code'] !== null) {
                $template = $this->templatesRepository->getByCode($data['subscribe_mail_template_code']);

                if (!$template) {
                    return new JsonApiResponse(404, [
                        'status' => 'error',
                        'code' => 'subscribe_template_not_found',
                        'message' => 'No template found for provided subscribe_mail_template_code: ' . $data['subscribe_mail_template_code'],
                    ]);
                }
                if ($template->mail_type->mail_type_category->code !== 'system') {
                    return new JsonApiResponse(400, [
                        'status' => 'error',
                        'code' => 'subscribe_template_not_allowed',
                        'message' => 'Subscribe_mail_template_id must belong under one of the system category newsletters: ' . $template->mail_type->mail_type_category->code,
                    ]);
                }

                $data['subscribe_mail_template_id'] = $template->id;
            } else {
                $data['subscribe_mail_template_id'] = null;
            }

            unset($data['subscribe_mail_template_code']);
        }

        if (array_key_exists('unsubscribe_mail_template_code', $data)) {
            if ($data['unsubscribe_mail_template_code'] !== null) {
                $template = $this->templatesRepository->getByCode($data['unsubscribe_mail_template_code']);

                if (!$template) {
                    return new JsonApiResponse(404, [
                        'status' => 'error',
                        'code' => 'unsubscribe_template_not_found',
                        'message' => 'No template found for provided unsubscribe_mail_template_code: ' . $data['unsubscribe_mail_template_code'],
                    ]);
                }
                if ($template->mail_type->mail_type_category->code !== 'system') {
                    return new JsonApiResponse(400, [
                        'status' => 'error',
                        'code' => 'unsubscribe_template_not_allowed',
                        'message' => 'Unsubscribe_mail_template_id must belong under one of the system category newsletters: ' . $template->mail_type->mail_type_category->code,
                    ]);
                }

                $data['unsubscribe_mail_template_id'] = $template->id;
            } else {
                $data['unsubscribe_mail_template_id'] = null;
            }

            unset($data['unsubscribe_mail_template_code']);
        }

        try {
            $list = $this->getList($data);
            if ($list) {
                $data['mail_type_category_id'] ??= $list->mail_type_category_id;
                $data['priority'] ??= $list->priority;
                $data['code'] ??= $list->code;
                $data['title'] ??= $list->title;
                $data['description'] ??= $list->description;

                $this->listsRepository->update($list, $data);
                $list = $this->listsRepository->find($list->id);
            } else {
                $list = $this->addList($data);
            }
        } catch (InvalidApiInputParamException $e) {
            return new JsonApiResponse($e->getCode(), [
                'status' => 'error',
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
        }

        return new JsonApiResponse(200, [
            'status' => 'ok',
            'data' => [
                'id' => $list->id,
                'code' => $list->code,
                'title' => $list->title,
                'sorting' => $list->sorting,
                'description' => $list->description,
                'mail_from' => $list->mail_from,
                'priority' => $list->priority,
                'mail_type_category_id' => $list->mail_type_category_id,
                'locked' => (bool) $list->locked,
                'public_listing' => (bool) $list->public_listing,
                'auto_subscribe' => (bool) $list->auto_subscribe,
                'image_url' => $list->image_url,
                'preview_url' => $list->preview_url,
                'page_url' => $list->page_url,
                'created_at' => $list->created_at->format(DATE_RFC3339),
                'updated_at' => $list->updated_at->format(DATE_RFC3339),
                'is_multi_variant' => (bool) $list->is_multi_variant,
                'default_variant_id' => $list->default_variant_id,
                'subscribe_mail_template_code' => $list->subscribe_mail_template->code ?? null,
                'unsubscribe_mail_template_code' => $list->unsubscribe_mail_template->code ?? null,
            ],
        ]);
    }

    private function getList($input)
    {
        if (isset($input['id'])) {
            return $this->listsRepository->find($input['id']);
        }

        if (isset($input['code'])) {
            return $this->listsRepository->findByCode($input['code'])->fetch();
        }

        return false;
    }

    private function getLastSortingNumberByCategory($categoryId)
    {
        $listsInCategory = $this->listsRepository->findByCategory($categoryId)
            ->order('mail_types.sorting')
            ->fetchAll();

        if (!empty($listsInCategory)) {
            return end($listsInCategory)->sorting + 1;
        }

        return 1;
    }

    private function addList($params)
    {
        $required = [
            'mail_type_category_id',
            'priority',
            'code',
            'title',
            'description',
        ];
        $missing = [];

        foreach ($required as $field) {
            if (!isset($params[$field])) {
                $missing[$field] = true;
            }
        }
        if (count($missing)) {
            throw new InvalidApiInputParamException(sprintf(
                'Required fields missing: %s',
                implode(', ', array_keys($missing))
            ), 400);
        }


        return $this->listsRepository->add(
            $params['mail_type_category_id'],
            $params['priority'],
            $params['code'],
            $params['title'],
            $params['sorting'] ?? $this->getLastSortingNumberByCategory($params['mail_type_category_id']),
            $params['auto_subscribe'] ?? false,
            $params['locked'] ?? false,
            $params['description'] ?? null,
            $params['preview_url'] ?? null,
            $params['page_url'] ?? null,
            $params['image_url'] ?? null,
            $params['public_listing'] ?? true,
            $params['mail_from'] ?? null,
            $params['subscribe_mail_template_id'] ?? null
        );
    }
}
