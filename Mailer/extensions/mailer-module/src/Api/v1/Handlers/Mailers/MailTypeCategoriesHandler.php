<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailTypeCategoriesHandler extends BaseHandler
{
    public function __construct(
        private ListCategoriesRepository $categoriesRepository
    ) {
        parent::__construct();
    }

    public function handle(array $params): ResponseInterface
    {
        $categories = $this->categoriesRepository->all()->fetchAll();

        $output = [];
        foreach ($categories as $category) {
            $item = new \stdClass();
            $item->id = $category->id;
            $item->title = $category->title;
            $item->sorting = $category->sorting;
            $item->show_title = (bool) $category->show_title;
            $item->code = $category->code;
            $output[] = $item;
        }

        return new JsonApiResponse(200, $output);
    }
}
