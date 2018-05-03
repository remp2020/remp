<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Generators\GeneratorFactory;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailGeneratorPreprocessHandler extends BaseHandler
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
            new InputParam(InputParam::TYPE_POST, 'data', InputParam::REQUIRED),
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

        $output = $generator->preprocessParameters(json_decode($params['data']));


        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output, 'generator_post_url'=>'http://something']);
    }
}
