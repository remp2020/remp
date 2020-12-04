<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Application\LinkGenerator;
use Remp\MailerModule\Models\Generators\GeneratorFactory;
use Remp\MailerModule\Models\Generators\PreprocessException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailGeneratorPreprocessHandler extends BaseHandler
{
    private $generatorFactory;

    private $sourceTemplatesRepository;

    public function __construct(
        LinkGenerator $linkGenerator,
        GeneratorFactory $generatorFactory,
        SourceTemplatesRepository $sourceTemplatesRepository
    ) {
        parent::__construct();
        $this->generatorFactory = $generatorFactory;
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function handle($params)
    {
        $json = file_get_contents("php://input");
        if (empty($json)) {
            $response = new JsonApiResponse(400, ['status' => 'error', 'message' => 'Empty request']);
            return $response;
        }

        $data = json_decode($json);

        if (!isset($data->source_template_id)) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Missing required json parameter \'source_template_id\'']);
        }
        if (!isset($data->data)) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Missing required json parameter \'data\'']);
        }

        $generator = null;
        $template = $this->sourceTemplatesRepository->find($data->source_template_id);
        if (!$template) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => "No source template associated with id {$data->source_template_id}"]);
        }

        try {
            $generator = $this->generatorFactory->get($template->generator);
        } catch (\Exception $ex) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => "Unregistered generator type {$template->generator}"]);
        }

        try {
            $output = $generator->preprocessParameters($data->data);
            if (!$output) {
                return new JsonApiResponse(400, [
                    'status' => 'error',
                    'message' => "Generator [{$template->generator}] used for source template [{$data->source_template_id}] does not support parameter preprocessing",
                ]);
            }
            $output->source_template_id = $data->source_template_id;
            return new JsonApiResponse(200, [
                'status' => 'ok',
                'data' => $output,
                'generator_post_url' => $this->linkGenerator->link('Mailer:MailGenerator:default')
            ]);
        } catch (PreprocessException $e) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
