<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Http\Url;
use Nette\Utils\ArrayHash;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\ArticleUrlParserWidget\ArticleUrlParserWidget;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class ArticleUrlParserGenerator implements IGenerator
{
    public $onSubmit;

    private array $contentProcessors = [];

    public function __construct(
        protected SourceTemplatesRepository $sourceTemplatesRepository,
        protected ContentInterface $content,
        protected readonly EngineFactory $engineFactory,
    ) {
    }

    public function setContentProcessor(string $domain, $contentProcessor)
    {
        $this->contentProcessors[$domain] = $contentProcessor;
    }

    public function generateForm(Form $form): void
    {
        $form->addTextArea('intro', 'Intro text')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('class', 'form-control wysiwyg-editor')
            ->getControlPrototype();

        $form->addTextArea('articles', 'Articles')
            ->setHtmlAttribute('rows', 7)
            ->setHtmlAttribute('class', 'form-control')
            ->setOption('description', 'Paste article URLs. Each on separate line.')
            ->setRequired(true)
            ->getControlPrototype();

        $form->addTextArea('external_intro', 'External intro text')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('class', 'form-control wysiwyg-editor')
            ->getControlPrototype();

        $form->addTextArea('external_articles', 'External articles')
            ->setHtmlAttribute('rows', 7)
            ->setHtmlAttribute('class', 'form-control')
            ->setOption('description', 'Paste article URLs from external website supported by the generator. Each on separate line.')
            ->getControlPrototype();

        $form->addTextArea('footer', 'Footer text')
            ->setHtmlAttribute('rows', 6)
            ->setHtmlAttribute('class', 'form-control wysiwyg-editor')
            ->getControlPrototype();

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        try {
            $output = $this->process((array)$values);

            $addonParams = [
                'render' => true,
                'errors' => $output['errors'],
            ];

            $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
        } catch (InvalidUrlException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('articles'))->setRequired(),
            (new PostInputParam('footer'))->setRequired(),
            (new PostInputParam('utm_campaign'))->setRequired(),
            (new PostInputParam('intro'))->setRequired(),
            (new PostInputParam('external_intro')),
            (new PostInputParam('external_articles')),
        ];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);

        if (!$sourceTemplate) {
            throw new \RuntimeException("Unable to find source template with ID [{$values['source_template_id']}]");
        }

        $errors = [];
        $urls = explode("\n", trim($values['articles']));
        $items = $this->readArticlesMetadata($urls, $errors);

        $externalUrls = explode("\n", trim($values['external_articles']));
        $externalItems = $this->readArticlesMetadata($externalUrls, $errors);

        $params = [
            'intro' => $values['intro'],
            'external_intro' => $values['external_intro'],
            'footer' => $values['footer'],
            'items' => $items,
            'external_items' => $externalItems,
        ];

        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
            'errors' => $errors,
        ];
    }

    public function getWidgets(): array
    {
        return [ArticleUrlParserWidget::class];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        return null;
    }

    private function readArticlesMetadata(array $urls, &$errors): array
    {
        $items = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                // people sometimes enter blank lines
                continue;
            }
            try {
                $parsedUrl = new Url($url);
                if (isset($this->contentProcessors[$parsedUrl->getDomain()])) {
                    $processor = $this->contentProcessors[$parsedUrl->getDomain()];
                    $meta = $processor->fetchUrlMeta($url);
                } else {
                    $meta = $this->content->fetchUrlMeta($url);
                }

                if ($meta) {
                    $items[$url] = $meta;
                } else {
                    $errors[] = $url;
                }
            } catch (InvalidUrlException $e) {
                $errors[] = $url;
            }
        }

        return $items;
    }
}
