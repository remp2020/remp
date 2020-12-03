<?php
declare(strict_types=1);

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Api\v1\Handlers\Mailers\ProcessException;
use Remp\MailerModule\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\PageMeta\ContentInterface;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class GenericBestPerformingArticlesGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    protected $content;

    private $engineFactory;

    public $onSubmit;

    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        ContentInterface $content,
        EngineFactory $engineFactory
    ) {
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->content = $content;
        $this->engineFactory = $engineFactory;
    }

    public function generateForm(Form $form): void
    {
        $form->addTextArea('articles', 'List of articles')
            ->setAttribute('rows', 4)
            ->setOption('description', 'Insert Url of every article - each on separate line')
            ->getControlPrototype()
            ->setAttribute('class', 'form-control html-editor');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $output = $this->process((array) $values);
        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent']);
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
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'dynamic', InputParam::OPTIONAL),
            new InputParam(InputParam::TYPE_POST, 'articles', InputParam::OPTIONAL),
            new InputParam(InputParam::TYPE_POST, 'articles_count', InputParam::OPTIONAL)
        ];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);
        $dynamic = filter_var($values['dynamic'] ?? null, FILTER_VALIDATE_BOOLEAN);

        $items = [];

        if ($dynamic) {
            if (!isset($values['articles_count'])) {
                throw new ProcessException("Dynamic email requires 'articles_count' parameter");
            }

            $articlesCount = (int) $values['articles_count'];
            for ($i = 1; $i <= $articlesCount; $i++) {
                // Insert Twig variables that will be replaced later
                $meta = new \stdClass();
                $meta->title = "{{article_{$i}_title}}";
                $meta->image = "{{article_{$i}_image}}";
                $meta->description = "{{article_{$i}_description}}";
                $items["{{article_{$i}_url}}"] = $meta;
            }
        } else {
            if (!isset($values['articles'])) {
                throw new ProcessException("Missing 'articles' parameter");
            }

            $urls = explode("\n", trim($values['articles']));
            foreach ($urls as $url) {
                $meta = $this->content->fetchUrlMeta($url);
                if ($meta) {
                    $items[$url] = $meta;
                }
            }
        }

        $params = [
            'items' => $items,
        ];
        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
        ];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        return null;
    }
}
