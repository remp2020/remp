<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Http\IResponse;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\GetInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class RenderTemplateApiHandler extends BaseHandler
{
    private TemplatesRepository $templatesRepository;
    private ContentGenerator $contentGenerator;
    private GeneratorInputFactory $generatorInputFactory;

    public function __construct(
        TemplatesRepository $templatesRepository,
        ContentGenerator $contentGenerator,
        GeneratorInputFactory $generatorInputFactory
    ) {
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
        $this->contentGenerator = $contentGenerator;
        $this->generatorInputFactory = $generatorInputFactory;
    }

    public function params(): array
    {
        return [
            (new GetInputParam('code'))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $code = $params['code'];

        $template = $this->templatesRepository->findBy('code', $code);

        if (!$template) {
            return new JsonApiResponse(IResponse::S404_NotFound, [
                'status' => 'error',
                'message' => "no such mail template: {$code}"
            ]);
        }

        $renderedTemplate = $this->contentGenerator->render($this->generatorInputFactory->create($template));
        $result = [
            'status' => 'ok',
            'html' => $renderedTemplate->html(),
            'text' => $renderedTemplate->text()
        ];

        return new JsonApiResponse(IResponse::S200_OK, $result);
    }
}
