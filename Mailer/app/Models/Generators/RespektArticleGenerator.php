<?php

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\RespektArticleParserWidget\RespektArticleParserWidget;
use Remp\Mailer\Models\PageMeta\Content\RespektContent;
use Remp\Mailer\Models\PageMeta\RespektMeta;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Models\Generators\SnippetArticleLocker;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;
use Tracy\Debugger;
use Tracy\ILogger;

class RespektArticleGenerator implements IGenerator
{
    public $onSubmit;

    public function __construct(
        private readonly ContentInterface $content,
        private readonly SourceTemplatesRepository $sourceTemplatesRepository,
        private readonly EngineFactory $engineFactory,
        private readonly SnippetArticleLocker $snippetArticleLocker,
    ) {
    }

    public function generateForm(Form $form): void
    {
        $form->addText('article', 'Article')
            ->setOption('description', 'Paste article URL.')
            ->setRequired()
            ->getControlPrototype()
            ->setHtmlAttribute('class', 'form-control html-editor');

        $form->addText('author_name', 'Author')
            ->setOption('description', 'Author name for use in notification email.');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        if (!$this->content instanceof RespektContent) {
            Debugger::log(self::class . ' depends on ' . RespektContent::class . '.', ILogger::ERROR);
            $sourceTemplate = null;
            if (isset($values['source_template_id'])) {
                $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);
            }
            $form->addError(sprintf(
                "Mail generator [%s] is not configured correctly. Contact developers.",
                $sourceTemplate->title ?? '',
            ));
            return;
        }

        try {
            $output = $this->process((array) $values);

            $addonParams = [
                'render' => true,
                'errors' => $output['errors'],
                'lockedHtmlContent' => $output['lockedHtmlContent'],
                'lockedTextContent' => $output['lockedTextContent'],
                'subject' => $output['subject'],
            ];

            $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
        } catch (InvalidUrlException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('article'))->setRequired(),
            (new PostInputParam('author_name')),
        ];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);

        $errors = [];

        $url = trim($values['article']);
        try {
            /** @var RespektMeta $article */
            $article = $this->content->fetchUrlMeta($url);
            if (!$article) {
                $errors[] = $url;
            }
        } catch (InvalidUrlException $e) {
            $errors[] = $url;
        }

        $params = [
            'article' => $article ?? null,
            'url' => $url,
            'author_name' => $values['author_name'] ?? null,
        ];

        $lockedParams = $params;
        if (isset($article)) {
            $unlockedContent = $article->unlockedContent . " " . SnippetArticleLocker::LOCKED_TEXT_PLACEHOLDER;
            $unlockedContent = $this->snippetArticleLocker->injectLockedMessage($unlockedContent);

            $lockedArticle = new RespektMeta(
                title: $article->getTitle(),
                image: $article->getImage(),
                authors: $article->getAuthors(),
                type: $article->type,
                subtitle: $article->subtitle,
                firstParagraph: $article->firstParagraph,
                firstContentPartType: $article->firstContentPartType,
                fullContent:$unlockedContent,
                imageTitle: $article->imageTitle,
                subject: $article->subject,
            );

            $lockedParams['article'] = $lockedArticle;
        }


        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
            'lockedHtmlContent' => $engine->render($sourceTemplate->content_html, $lockedParams),
            'lockedTextContent' => strip_tags($engine->render($sourceTemplate->content_text, $lockedParams)),
            'errors' => $errors,
            'subject' => $article->subject ?? null,
        ];
    }

    public function getWidgets(): array
    {
        return [RespektArticleParserWidget::class];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        return null;
    }
}
