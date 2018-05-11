<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Remp\MailerModule\Api\v1\Handlers\Mailers\InvalidUrlException;
use Remp\MailerModule\PageMeta\DenniknContent;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class UrlParserGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    public $onSubmit;

    public function __construct(SourceTemplatesRepository $sourceTemplatesRepository)
    {
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
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
            $meta = Utils::fetchUrlMeta($url, new DenniknContent());
            if ($meta) {
                $items[$url] = $meta;
            }
        }

        $loader = new \Twig_Loader_Array([
            'html_template' => $sourceTemplate->content_html,
            'text_template' => $sourceTemplate->content_text,
        ]);
        $twig = new \Twig_Environment($loader);
        $params = [
            'intro' => $values->intro,
            'footer' => $values->footer,
            'items' => $items,
            'utm_campaign' => $values->utm_campaign,
        ];

        $output = [];
        $output['htmlContent'] = $twig->render('html_template', $params);
        $output['textContent'] = $twig->render('text_template', $params);
        return $output;
    }

    public function getWidgets()
    {
        return [];
    }

    public function preprocessParameters($data)
    {
        return [];
    }
}
