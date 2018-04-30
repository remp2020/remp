<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Generators\GeneratorFactory;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailGeneratorHandler extends BaseHandler
{
    private $generatorFactory;

    private $sourceTemplatesRepository;

    public function __construct(GeneratorFactory $generatorFactory, SourceTemplatesRepository $sourceTemplatesRepository)
    {
        parent::__construct();
        $this->generatorFactory = $generatorFactory;
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'generator', InputParam::REQUIRED),
        ];
    }

    public function handle($params)
    {
        $generator = null;
        try {
            $generator = $this->generatorFactory->get($params['generator']);
        } catch (\Exception $ex){
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Unknown generator type.']);
        }

        $paramsProcessor = new GeneratorParamsProcessor($generator->apiParams());
        $errors = $paramsProcessor->getErrors();
        if (!empty($errors)) {
            return $this->getMissingParamsResponse($errors);

        }

        $output = $generator->process((object) $paramsProcessor->getValues());

        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
    }

    private function getMissingParamsResponse(array $errors)
    {
        return new JsonApiResponse(400, [
            'status' => 'error', 'message' => 'Some fields are invalid or missing', 'missingFields' => $errors
        ]);
    }
}