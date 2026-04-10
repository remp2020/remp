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
            (new PostInputParam('post_json'))->setRequired(),
            (new PostInputParam('author_json')),
            (new PostInputParam('tag_json')),
            (new PostInputParam('category_json')),
        ];
    }

    public function process(array $input): array
    {
        $engine = $this->engineFactory->engine();
        $sourceTemplate = $this->sourceTemplatesRepository->findBy('code', $input['source_template_code']);

        $params = array_filter([
            'post' => Json::decode($input['post_json']),
            'author' => Json::decode($input['author_json'] ?? 'null'),
            'tag' => Json::decode($input['tag_json'] ?? 'null'),
            'category' => Json::decode($input['category_json'] ?? 'null'),
        ]);

        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => $engine->render($sourceTemplate->content_text, $params),
        ];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        $output = new ArrayHash();

        $output->post_json = Json::encode($data->post);

        if (isset($data->author)) {
            $output->author_json = Json::encode($data->author);
        }
        if (isset($data->tag)) {
            $output->tag_json = Json::encode($data->tag);
        }
        if (isset($data->category)) {
            $output->category_json = Json::encode($data->category);
        }

        return $output;
    }
}
