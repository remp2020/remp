<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Remp\MailerModule\PageMeta\GenericPageContent;
use Remp\MailerModule\PageMeta\TransportInterface;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class GenericBestPerformingArticlesGenerator implements IGenerator
{
    protected $sourceTemplatesRepository;

    public $onSubmit;

    private $transport;

    public function __construct(TransportInterface $transport, SourceTemplatesRepository $sourceTemplatesRepository)
    {
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->transport = $transport;
    }

    public function generateForm(Form $form)
    {
        $form->addTextArea('articles', 'List of articles')
            ->setAttribute('rows', 4)
            ->setOption('description', 'Insert Url of every article - each on separate line')
            ->getControlPrototype()
            ->setAttribute('class', 'form-control html-editor');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit)
    {
        $this->onSubmit = $onSubmit;
    }

    public function getWidgets()
    {
        return [];
    }

    public function apiParams()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'articles', InputParam::REQUIRED),
        ];
    }

    public function process($values)
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values->source_template_id);

        $items = [];
        $urls = explode("\n", trim($values->articles));
        foreach ($urls as $url) {
            $meta = Utils::fetchUrlMeta($url, new GenericPageContent(), $this->transport);
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
            'items' => $items,
        ];

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
