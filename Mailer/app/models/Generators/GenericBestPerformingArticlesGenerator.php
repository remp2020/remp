<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Remp\MailerModule\Api\v1\Handlers\Mailers\ProcessException;
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

    public function formSucceeded($form, $values)
    {
        $output = $this->process($values);
        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent']);
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
            new InputParam(InputParam::TYPE_POST, 'dynamic', InputParam::OPTIONAL),
            new InputParam(InputParam::TYPE_POST, 'articles', InputParam::OPTIONAL),
            new InputParam(InputParam::TYPE_POST, 'articles_count', InputParam::OPTIONAL)
        ];
    }

    public function process($values)
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($values->source_template_id);
        $dynamic = filter_var($values->dynamic, FILTER_VALIDATE_BOOLEAN);

        $items = [];
        if ($dynamic) {
            if (!isset($values->articles_count)) {
                throw new ProcessException("Dynamic email requires 'article_count' parameter");
            }

            $articlesCount = (int) $values->articles_count;
            for ($i = 1; $i <= $articlesCount; $i++) {
                // Insert Twig variables that will be replaced later
                $meta = new \stdClass();
                $meta->title = "{{article_{$i}_title}}";
                $meta->image = "{{article_{$i}_image}}";
                $meta->description = "{{article_{$i}_description}}";
                $items["{{article_{$i}_url}}"] = $meta;
            }
        } else {
            if (!isset($values->articles)) {
                throw new ProcessException("Missing 'articles' parameter");
            }

            $urls = explode("\n", trim($values->articles));
            foreach ($urls as $url) {
                $meta = Utils::fetchUrlMeta($url, new GenericPageContent(), $this->transport);
                if ($meta) {
                    $items[$url] = $meta;
                }
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
