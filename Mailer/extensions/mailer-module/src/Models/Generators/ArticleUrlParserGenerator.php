<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class ArticleUrlParserGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    protected $content;

    public $onSubmit;

    private $engineFactory;

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
        $form->addTextArea('intro', 'Intro text')
            ->setHtmlAttribute('rows', 4)
            ->getControlPrototype()
            ->setHtmlAttribute('class', 'form-control html-editor');

        $form->addTextArea('articles', 'Article')
            ->setHtmlAttribute('rows', 7)
            ->setOption('description', 'Paste article URLs. Each on separate line.')
            ->setRequired(true)
            ->getControlPrototype()
            ->setHtmlAttribute('class', 'form-control html-editor');

        $form->addTextArea('footer', 'Footer text')
            ->setHtmlAttribute('rows', 6)
            ->getControlPrototype()
            ->setHtmlAttribute('class', 'form-control html-editor');

        $form->addText('rtm_campaign', 'RTM campaign');

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
            $this->onSubmit->__invoke($output['htmlContent'], $output['textContent']);
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
            'intro' => $values['intro'],
            'footer' => $values['footer'],
            'items' => $items,
            'rtm_campaign' => $values['rtm_campaign'],
            // UTM Fallback -- will be removed
            'utm_campaign' => $values['rtm_campaign'],
        ];

        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
        ];
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        return null;
    }
}
