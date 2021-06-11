<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Models\Generators\GeneratorFactory;
use Remp\MailerModule\Models\Generators\ProcessException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

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

    public function params(): array
    {
        return [
            (new PostInputParam('source_template_id')),
            (new PostInputParam('source_template_code')),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $generator = null;

        if (!isset($params['source_template_id']) && !isset($params['source_template_code'])) {
            return new JsonApiResponse(400, [
                'status' => 'error',
                'message' => 'You have to specify either \'source_template_id\' or \'source_template_code\' param to identify generators source template',
            ]);
        }

        $template = null;
        if (isset($params['source_template_id'])) {
            $template = $this->sourceTemplatesRepository->find($params['source_template_id']);
        }
        if (!$template && isset($params['source_template_code'])) {
            $template = $this->sourceTemplatesRepository->getByCode($params['source_template_code']);
        }
        if (!$template) {
            return new JsonApiResponse(404, [
                'status' => 'error',
                'message' => "Cannot find the source template: " . ($params['source_template_id'] ?? $params['source_template_code']),
            ]);
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
            $generatorParams = $paramsProcessor->getValues();
            $generatorParams['source_template_id'] = $template->id;
            $output = $generator->process($generatorParams);
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
