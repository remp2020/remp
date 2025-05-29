<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Nette\Application\UI\Form;
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

    public function __construct(
        protected SourceTemplatesRepository $sourceTemplatesRepository,
        protected ContentInterface $content,
        private EngineFactory $engineFactory
    ) {
    }

    public function generateForm(Form $form): void
    {
        $form->addTextArea('intro', 'Intro text')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('class', 'form-control trumbowyg-editor')
            ->getControlPrototype();

        $form->addTextArea('articles', 'Article')
            ->setHtmlAttribute('rows', 7)
            ->setHtmlAttribute('class', 'form-control')
            ->setOption('description', 'Paste article URLs. Each on separate line.')
            ->setRequired(true)
            ->getControlPrototype();

        $form->addTextArea('footer', 'Footer text')
            ->setHtmlAttribute('rows', 6)
            ->setHtmlAttribute('class', 'form-control trumbowyg-editor')
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
        ];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);

        if (!$sourceTemplate) {
            throw new \RuntimeException("Unable to find source template with ID [{$values['source_template_id']}]");
        }

        $items = [];
        $errors = [];

        $urls = explode("\n", trim($values['articles']));
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                // people sometimes enter blank lines
                continue;
            }
            try {
                $meta = $this->content->fetchUrlMeta($url);
                if ($meta) {
                    $items[$url] = $meta;
                } else {
                    $errors[] = $url;
                }
            } catch (InvalidUrlException $e) {
                $errors[] = $url;
            }
        }

        $params = [
            'intro' => $values['intro'],
            'footer' => $values['footer'],
            'items' => $items,
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
}
