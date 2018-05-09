<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Remp\MailerModule\Components\GeneratorWidgets\Widgets\NewsfilterWidget;
use Remp\MailerModule\Repository\SourceTemplatesRepository;

class NewsfilterGenerator implements IGenerator
{
    protected $mailSourceTemplateRepository;

    public $onSubmit;

    public function __construct(SourceTemplatesRepository $mailSourceTemplateRepository)
    {
        $this->mailSourceTemplateRepository = $mailSourceTemplateRepository;
    }

    public function generate(Form $form)
    {
        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addText('url', 'Newsfilter URL')
            ->addRule(Form::URL)
            ->setRequired("Field 'Newsfilter URL' is required.");

        $form->addText('editor', 'Editor')
            ->setRequired("Field 'Editor' is required.");

        $form->addTextArea('summary', 'Summary')
            ->setAttribute('rows', 3)
            ->setRequired("Field 'Summary' is required.");

        $form->addTextArea('newsfilter_html', 'HTML')
            ->setAttribute('rows', 20)
            ->setAttribute('class', 'form-control html-editor')
            ->getControlPrototype();

        $form->addSubmit('send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-magic"></i> ' . 'Generate');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit)
    {
        $this->onSubmit = $onSubmit;
    }

    public function getWidgets()
    {
        return [NewsfilterWidget::class];
    }

    public function formSucceeded($form, $values)
    {
        $sourceTemplate = $this->mailSourceTemplateRepository->find($values->source_template_id);
        $html = $values->newsfilter_html;
        $lockedHtml = $this->getLockedHtml($values->newsfilter_html, $values->url);
        $lockedText = $lockedHtml;

        $htmlRules = [
            // remove newsfilter editor (it's being entered extra)
            ['from' => '/<p style="text-align: right;"><em>.*/im', 'to' => ''],

            // <h*> tags matching
            ['from' => '/<h2/i', 'to' => '<h3'],
            ['from' => '/<\/h2/i', 'to' => '</h3'],

            // width/height customizations for images
            ['from' => '/(<img.*?)( width="\d+")/i', 'to' => '$1'],
            ['from' => '/(<img.*?)( height="\d+")/i', 'to' => '$1'],
            ['from' => '/<img/i', 'to' => '<img style="max-width: 500px"'],

            // remove wordpress tags
            ['from' => '/\[\/?articlelink.*?\]/i', 'to' => ''],
            ['from' => '/\[\/?lock\]/i', 'to' => ''],
            ['from' => '/\[\/?greybox\]/i', 'to' => ''],
            ['from' => '/\[\/?caption.*?\]/i', 'to' => ''],

            // remove all new lines from within <em> tags; mostly for greybox quotes
            ['from' => '/(\<em\>.*?)\n(.*?\<\/em\>)/i', 'to' => '$1 $2'],

            // wrap within <p> if line starts with strong, a, b, u, i, em, span tags, unicode character, unicode number
            ['from' => '/^((<strong|<a|<b|<u|<i|<em|<span|\p{L}+|\p{N}+).*)/im', 'to' => '<p>$1</p>'],
        ];
        foreach ($htmlRules as $rule) {
            $html = preg_replace($rule['from'], $rule['to'], $html);
            $lockedHtml = preg_replace($rule['from'], $rule['to'], $lockedHtml);
        }

        $textRules = [
            // remove newsfilter editor (it's being entered extra)
            ['from' => '/<p style="text-align: right;"><em>.*/im', 'to' => ''],

            // <h*> tags matching
            ['from' => '/<h3/i', 'to' => "\n<h3"],
            ['from' => '/<h2.*?>/i', 'to' => ''],
            ['from' => '/<\/h2.*?>/i', 'to' => "\n"],

            // remove images
            ['from' => '/<img.*?<\/img>/i', 'to' => ''],

            // remove non-breaking spaces
            ['from' => '/&nbsp;/i', 'to' => ''],

            // remove wordpress tags
            ['from' => '/\[\/?articlelink.*?\]/i', 'to' => ''],
            ['from' => '/\[\/?greybox\]/i', 'to' => ''],
            ['from' => '/\[\/?lock\]/i', 'to' => ''],
            ['from' => '/\[\/?caption.*?\]/i', 'to' => ''],

            // remove all new lines from within <em> tags; mostly for greybox quotes
            ['from' => '/(\<em\>.*?)\n(.*?\<\/em\>)/i', 'to' => '$1 $2'],

            // "jednou vetou"
            ['from' => '/<a.*?href="(.*?)".*?>(.*?)<\/a>.*?(\(.*?\))/i', 'to' => "$2 $3\n$1"],
            ['from' => '/<a.*?href="(.*?)".*?>(.*?)<\/a>(.*)/im', 'to' => "$2$3\n$1"],

            // remove shooty section (inlined image was already removed
            ['from' => "/.*shooty.*/im", 'to' => ""],

            // trim lots of new lines (twice, just in case)
            ['from' => "/\n\n\n/", 'to' => "\n\n"],
            ['from' => "/\n\n\n/", 'to' => "\n\n"],
        ];
        $text = $values->newsfilter_html;
        foreach ($textRules as $rule) {
            $text = preg_replace($rule['from'], $rule['to'], $text);
            $lockedText = preg_replace($rule['from'], $rule['to'], $lockedText);
        }

        $loader = new \Twig_Loader_Array([
            'html_template' => $sourceTemplate->content_html,
            'text_template' => $sourceTemplate->content_text,
        ]);
        $twig = new \Twig_Environment($loader);
        $params = [
            'title' => $values->title,
            'editor' => $values->editor,
            'summary' => $values->summary,
            'url' => $values->url,
            'html' => $html,
            'text' => strip_tags($text),
        ];
        $lockedParams = [
            'title' => $values->title,
            'editor' => $values->editor,
            'url' => $values->url,
            'summary' => $values->summary,
            'html' => $lockedHtml,
            'text' => strip_tags($lockedText),
        ];

        $htmlContent = $twig->render('html_template', $params);
        $textContent = $twig->render('text_template', $params);

        $addonParams = [
            'lockedHtmlContent' => $twig->render('html_template', $lockedParams),
            'lockedTextContent' => $twig->render('text_template', $lockedParams),
            'newsfilterTitle' => $values->title,
            'render' => true
        ];

        $this->onSubmit->__invoke($htmlContent, $textContent, $addonParams);
    }

    private function getLockedHtml($fullHtml, $newsfilterLink)
    {
        $newHtml = '';
        $cacheHtml = '';
        $quit = false;
        foreach (explode("\n", $fullHtml) as $line) {
            $cacheHtml .= $line . "\n";
            if (strpos($line, '<h2') !== false) {
                $newHtml .= $cacheHtml;
                $cacheHtml = '';

                if ($quit) {
                    $newHtml .= <<<HTML
<p><a 
    style="display: block; margin: 0 0 20px; padding: 10px; text-decoration: none; text-align: center; font-weight: bold; color: #ffffff; background: #249fdc;" 
    href="{$newsfilterLink}/{{ autologin }}">
    Pokračovanie Newsfiltra - kliknite sem
</a></p>
HTML;
                    return $newHtml;
                }
            }
            if (strpos($line, '[lock]') !== false) {
                $quit = true;
            }
        }
        $newHtml .= $cacheHtml;
        return $newHtml;
    }
}
