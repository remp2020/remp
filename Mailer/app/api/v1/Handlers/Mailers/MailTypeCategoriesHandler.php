<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Utils\Arrays;
use Remp\MailerModule\ActiveRow;
use Remp\MailerModule\Repository\ListCategoriesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTypeCategoriesHandler extends BaseHandler
{

    private $categoriesRepository;

    public function __construct(
        ListCategoriesRepository $categoriesRepository
    ) {
        parent::__construct();
        $this->categoriesRepository = $categoriesRepository;
    }

    public function handle($params)
    {
        $categories = $this->categoriesRepository->all()->fetchAll();

        $output = Arrays::map($categories, function (ActiveRow $row) {
            return [
                'id' => $row->id,
                'title' => $row->title,
                'sorting' => $row->sorting,
                'show_title' => $row->show_title
            ];
        });

        return new JsonApiResponse(200, $output);
    }
}
