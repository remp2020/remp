<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Http\Response;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repository\ListsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTypesListingHandler extends BaseHandler
{
    private $listsRepository;
    use JsonValidationTrait;

    public function __construct(
        ListsRepository $listsRepository
    ) {
        parent::__construct();
        $this->listsRepository = $listsRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'code', InputParam::OPTIONAL),
            new InputParam(InputParam::TYPE_GET, 'public_listing', InputParam::OPTIONAL)
        ];
    }

    public function handle($params)
    {
        $results = $this->listsRepository->all();

        if (isset($params['code'])) {
            $results->where(['code' => $params['code']]);
        }

        if (isset($params['public_listing'])) {
            $publicListing = filter_var($params['public_listing'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($publicListing === null) {
                return new JsonApiResponse(Response::S400_BAD_REQUEST, ['status' => 'error', 'message' => 'Invalid value provided for param public_listing: ' . $params['public_listing']]);
            }
            $results->where(['public_listing' => $publicListing]);
        }

        $output = [];
        foreach ($results as $row) {
            $item = new \stdClass();
            $item->id = $row->id;
            $item->code = $row->code;
            $item->image_url = $row->image_url;
            $item->preview_url = $row->preview_url;
            $item->title = $row->title;
            $item->description = $row->description;
            $item->mail_type_category_id = $row->mail_type_category_id;
            $item->locked = (bool) $row->locked;
            $item->is_multi_variant = (bool) $row->is_multi_variant;
            $item->sorting = $row->sorting;
            $item->variants = $row->related('mail_type_variants.mail_type_id')->order('sorting')->fetchPairs('id', 'title');
            $output[] = $item;
        }

        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
    }
}
