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

        $output = [];
        foreach ($categories as $category) {
            $item = new \stdClass();
            $item->id = $category->id;
            $item->title = $category->title;
            $item->sorting = $category->sorting;
            $item->show_title = (bool) $category->show_title;
            $output[] = $item;
        }

        return new JsonApiResponse(200, $output);
    }
}
