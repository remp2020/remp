<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\Validators;
use Remp\MailerModule\PageMeta\GuzzleTransport;
use Remp\MailerModule\PageMeta\PageMeta;
use Remp\MailerModule\PageMeta\TyzdenContent;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class MinutaDigestGenerator implements IGenerator
{
    protected $sourceTemplateRepository;

    public $onSubmit;

    public function __construct(SourceTemplatesRepository $sourceTemplateRepository)
    {
        $this->sourceTemplateRepository = $sourceTemplateRepository;
    }

    public function onSubmit(callable $onSubmit)
    {
        $this->onSubmit = $onSubmit;
    }

    private function fetchUrl($url)
    {
        $pageMeta = new PageMeta(new GuzzleTransport(), new TyzdenContent());
        $meta = $pageMeta->getPageMeta(Utils::removeRefUrlAttribute($url));
        if ($meta) {
            return $meta;
        }
        return false;
    }

    public function formSucceeded($form, $values)
    {
        $output = $this->process($values);
        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent']);
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
        $sourceTemplate = $this->sourceTemplateRepository->find($values->source_template_id);

        $posts = [];
        $urls = explode("\n", $values->posts);
        foreach ($urls as $url) {
            if (Validators::isUrl($url)) {
                $posts[$url] = $this->fetchUrl($url);
            }
        }

        $params = [
            'posts' => $posts,
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

    public function preprocessParameters($data)
    {
        return [];
    }
}
