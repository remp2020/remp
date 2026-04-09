<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class HubNotificationGenerator implements IGenerator
{
    public $onSubmit;

    public function __construct(
        private readonly EngineFactory $engineFactory,
        private readonly SourceTemplatesRepository $sourceTemplatesRepository,
    ) {
    }

    public function generateForm(Form $form): void
    {
        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
    }

    public function onSubmit(callable $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('source_template_code'))->setRequired(),
            (new PostInputParam('params_json')),
        ];
    }

    public function process(array $input): array
    {
        $params = [];
        if (isset($input['params_json'])) {
            $params = Json::decode($input['params_json'], forceArrays: true);
        }

        $engine = $this->engineFactory->engine();
        $sourceTemplate = $this->sourceTemplatesRepository->find($input['source_template_id']);

        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => $engine->render($sourceTemplate->content_text, $params),
        ];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        $output = new ArrayHash();

        $output->post = $data->post;

        if (isset($data->author)) {
            $output->author = $data->author;
        }
        if (isset($data->tag)) {
            $output->tag = $data->tag;
        }
        if (isset($data->category)) {
            $output->category = $data->category;
        }

        return $output;
    }
}
