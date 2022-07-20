<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Models\Generators\GeneratorFactory;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\GetInputParam;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

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

    public function params(): array
    {
        return [
            new GetInputParam('generator')
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $generator = $params['generator'];
        if (!$generator) {
            $generator = $this->generatorFactory->keys();
        }
        $results = $this->sourceTemplatesRepository->all()
            ->where(['generator' => $generator])
            ->select('id,title')
            ->fetchAll();

        $output = [];
        foreach ($results as $row) {
            $item = new \stdClass();
            $item->id = $row->id;
            $item->title = $row->title;
            $output[] = $item;
        }

        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
    }
}
