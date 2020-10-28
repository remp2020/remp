<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Api\v1\Handlers\Mailers\InvalidUrlException;
use Remp\MailerModule\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\PageMeta\ContentInterface;
use Remp\MailerModule\PageMeta\TransportInterface;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class UrlParserGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    protected $content;

    public $onSubmit;

    private $transport;

    private $engineFactory;

    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        TransportInterface $transport,
        ContentInterface $content,
        EngineFactory $engineFactory
    ) {
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->transport = $transport;
        $this->content = $content;
        $this->engineFactory = $engineFactory;
    }

    public function generateForm(Form $form)
    {
        $form->addTextArea('intro', 'Intro text')
            ->setAttribute('rows', 4)
            ->getControlPrototype()
            ->setAttribute('class', 'form-control html-editor');

        $form->addTextArea('articles', 'Article')
            ->setAttribute('rows', 7)
            ->setOption('description', 'Paste article Urls. Each on separate line.')
            ->getControlPrototype()
            ->setAttribute('class', 'form-control html-editor');

        $form->addTextArea('footer', 'Footer text')
            ->setAttribute('rows', 6)
            ->getControlPrototype()
            ->setAttribute('class', 'form-control html-editor');

        $form->addText('utm_campaign', 'UTM campaign');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit)
    {
        $this->onSubmit = $onSubmit;
    }

    public function formSucceeded($form, $values)
    {
        try {
            $output = $this->process($values);
            $this->onSubmit->__invoke($output['htmlContent'], $output['textContent']);
        } catch (InvalidUrlException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function apiParams()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'articles', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'footer', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'utm_campaign', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'intro', InputParam::REQUIRED)
        ];
    }

    public function process($values)
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values->source_template_id);

        $items = [];
        $urls = explode("\n", trim($values->articles));
        foreach ($urls as $url) {
            $url = trim($url);
            $meta = $this->content->fetchUrlMeta($url);
            if ($meta) {
                $items[$url] = $meta;
            }
        }

        $params = [
            'intro' => $values->intro,
            'footer' => $values->footer,
            'items' => $items,
            'utm_campaign' => $values->utm_campaign,
        ];

        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
        ];
    }

    public function getWidgets()
    {
        return [];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        return null;
    }
}
