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

class MinutaAlertGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    protected $content;

    private $engineFactory;

    public $onSubmit;

    private $transport;

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

    public function generateForm(Form $form)
    {
        $form->addText('post', 'Url of post');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function apiParams()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'post', InputParam::REQUIRED)
        ];
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

    public function getWidgets()
    {
        return [];
    }

    public function process($values)
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values->source_template_id);

        $post = $this->content->fetchUrlMeta($values->post);

        $params = [
            'post' => $post,
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
