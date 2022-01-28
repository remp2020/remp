<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\ArticleUrlParserWidget\ArticleUrlParserWidget;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Content\ShopContentInterface;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class ShopUrlParserGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    protected $shopContent;

    public $onSubmit;

    private $engineFactory;

    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        ShopContentInterface $shopContent,
        EngineFactory $engineFactory
    ) {
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->shopContent = $shopContent;
        $this->engineFactory = $engineFactory;
    }

    public function generateForm(Form $form): void
    {
        $form->addTextArea('intro', 'Intro text')
            ->setHtmlAttribute('rows', 4)
            ->getControlPrototype()
            ->setHtmlAttribute('class', 'form-control html-editor');

        $form->addTextArea('products', 'Product')
            ->setHtmlAttribute('rows', 7)
            ->setOption('description', 'Paste products URLs (books and e-books). Each on separate line.')
            ->setRequired(true)
            ->getControlPrototype()
            ->setHtmlAttribute('class', 'form-control html-editor');

        $form->addTextArea('footer', 'Footer text')
            ->setHtmlAttribute('rows', 6)
            ->getControlPrototype()
            ->setHtmlAttribute('class', 'form-control html-editor');

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
            (new PostInputParam('products'))->setRequired(),
            (new PostInputParam('footer'))->setRequired(),
            (new PostInputParam('intro'))->setRequired(),
        ];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);

        $items = [];
        $errors = [];

        $urls = explode("\n", trim($values['products']));
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                // people sometimes enter blank lines
                continue;
            }

            try {
                $meta = $this->shopContent->fetchUrlMeta($url);
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
