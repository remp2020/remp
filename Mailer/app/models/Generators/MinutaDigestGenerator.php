<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Validators;
use Remp\MailerModule\Api\v1\Handlers\Mailers\InvalidUrlException;
use Remp\MailerModule\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\PageMeta\ContentInterface;
use Remp\MailerModule\PageMeta\TransportInterface;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class MinutaDigestGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    protected $content;

    public $onSubmit;

    private $transport;

    private $engineFactory;

    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        TransportInterface $transporter,
        ContentInterface $content,
        EngineFactory $engineFactory
    ) {
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->transport = $transporter;
        $this->content = $content;
        $this->engineFactory = $engineFactory;
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
            new InputParam(InputParam::TYPE_POST, 'posts', InputParam::REQUIRED)
        ];
    }

    public function process($values)
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values->source_template_id);

        $posts = [];
        $urls = explode("\n", $values->posts);
        foreach ($urls as $url) {
            $url = trim($url);
            if (Validators::isUrl($url)) {
                $posts[$url] = $this->content->fetchUrlMeta($url);
            }
        }

        $params = [
            'posts' => $posts,
        ];

        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
        ];
    }

    public function generateForm(Form $form)
    {
        $form->addTextArea('posts', 'List of posts')
            ->setAttribute('rows', 4)
            ->setOption('description', 'Insert URLs for Minutky - each on separate line')
            ->getControlPrototype()
            ->setAttribute('class', 'form-control html-editor');

        $form->onSuccess[] = [$this, 'formSucceeded'];
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
