<?php
declare(strict_types=1);

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\PageMeta\ContentInterface;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class DennikNBestPerformingArticlesGenerator implements IGenerator
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

    public function onSubmit(callable $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $output = $this->process((array) $values);
        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent']);
    }

    public function apiParams(): array
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'articles', InputParam::REQUIRED),
        ];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);

        $items = [];
        $urls = explode("\n", trim($values['articles']));
        foreach ($urls as $url) {
            $url = trim($url);
            $meta = $this->content->fetchUrlMeta($url);
            if ($meta) {
                $items[$url] = $meta;
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
