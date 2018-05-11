<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Remp\MailerModule\Api\v1\Handlers\Mailers\InvalidUrlException;
use Remp\MailerModule\PageMeta\TyzdenContent;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class MinutaAlertGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    public $onSubmit;

    public function __construct(SourceTemplatesRepository $sourceTemplatesRepository)
    {
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
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

        $post = Utils::fetchUrlMeta($values->post, new TyzdenContent());

        $params = [
            'post' => $post,
            'autologin' => '{{ autologin }}',
        ];

        $loader = new \Twig_Loader_Array([
            'html_template' => $sourceTemplate->content_html,
            'text_template' => $sourceTemplate->content_text,
        ]);
        $twig = new \Twig_Environment($loader);

        $output = [];
        $output['htmlContent'] = $twig->render('html_template', $params);
        $output['textContent'] = $twig->render('text_template', $params);
        return $output;
    }

    public function preprocessParameters($data)
    {
        return [];
    }
}
