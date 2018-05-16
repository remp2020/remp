<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Generators\GeneratorFactory;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;

class GeneratorTemplatesListingHandler extends BaseHandler
{
    private $generatorFactory;

    private $sourceTemplatesRepository;

    public function __construct(GeneratorFactory $generatorFactory, SourceTemplatesRepository $sourceTemplatesRepository)
    {
        parent::__construct();
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->generatorFactory = $generatorFactory;
    }

    public function handle($params)
    {
        $keys = $this->generatorFactory->keys();
        $results = $this->sourceTemplatesRepository->getTable()
            ->where(['generator' => $keys])
            ->select('id,title')->fetchAll();

        $output = [];
        foreach ($results as $row) {
            $output[] = $row->toArray();
        }

        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
    }
}
