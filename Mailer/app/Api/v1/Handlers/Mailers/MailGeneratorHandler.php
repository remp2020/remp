<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Models\Generators\GeneratorFactory;
use Remp\MailerModule\Models\Generators\ProcessException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
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
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
        ];
    }

    public function handle($params)
    {
        $generator = null;
        $template = $this->sourceTemplatesRepository->find($params['source_template_id']);
        if (!$template) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => "No source template associated with id {$params['source_template_id']}"]);
        }

        try {
            $generator = $this->generatorFactory->get($template->generator);
        } catch (\Exception $ex) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => "Unregistered generator type {$template->generator}"]);
        }

        $paramsProcessor = new GeneratorParamsProcessor($generator->apiParams());
        $errors = $paramsProcessor->getErrors();
        if (!empty($errors)) {
            return $this->getMissingParamsResponse($errors);
        }

        try {
            $output = $generator->process($paramsProcessor->getValues());
            return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
        } catch (ProcessException $exception) {
            return new JsonApiResponse(400, [
                'status' => 'error', 'message' => $exception->getMessage()
            ]);
        }
    }

    private function getMissingParamsResponse(array $errors)
    {
        return new JsonApiResponse(400, [
            'status' => 'error', 'message' => 'Some fields are invalid or missing', 'missingFields' => $errors
        ]);
    }
}
