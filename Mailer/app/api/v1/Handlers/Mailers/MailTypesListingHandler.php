<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Repository\ListsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTypesListingHandler extends BaseHandler
{

    private $listsRepository;

    public function __construct(
        ListsRepository $listsRepository
    ) {
        parent::__construct();
        $this->listsRepository = $listsRepository;
    }

    public function handle($params)
    {
        $results = $this->listsRepository->all();

        $output = [];
        foreach ($results as $row) {
            $item = new \stdClass();
            $item->code = $row->code;
            $item->title = $row->title;
            $output[] = $item;
        }

        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
    }
}
