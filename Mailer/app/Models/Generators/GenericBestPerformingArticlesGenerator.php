<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\PostInputParam;

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
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('class', 'form-control html-editor')
            ->setOption('description', 'Insert Url of every article - each on separate line');

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
            new PostInputParam('dynamic'),
            new PostInputParam('articles'),
            new PostInputParam('articles_count'),
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
