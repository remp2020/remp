<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Remp\MailerModule\Api\v1\Handlers\Mailers\PreprocessException;
use Remp\MailerModule\Components\GeneratorWidgets\Widgets\MediaBriefingWidget;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;
use GuzzleHttp\Client;
use Remp\MailerModule\Generators\MediaBriefingHelpers;

class MediaBriefingGenerator implements IGenerator
{
    protected $mailSourceTemplateRepository;

    public $onSubmit;

    public function __construct(SourceTemplatesRepository $mailSourceTemplateRepository)
    {
        $this->mailSourceTemplateRepository = $mailSourceTemplateRepository;
    }

    public function apiParams()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'mediabriefing_html', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'url', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'title', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'sub_title', InputParam::REQUIRED),
        ];
    }

    public function getWidgets()
    {
        return [MediaBriefingWidget::class];
    }

    public function process($values)
    {
        $helpers = new MediaBriefingHelpers;
        $sourceTemplate = $this->mailSourceTemplateRepository->find($values->source_template_id);
        $html = '';

        $post = $values->mediabriefing_html;

        list($captionTemplate, $liTemplate, $hrTemplate) = $this->getTemplates();

        // remove grayboxes
        $post = preg_replace('/\[greybox\].*?\[\/greybox\]/is', '', $post);

        // remove paragraphs
        $post = preg_replace('/<p.*?>(.*?)<\/p>/is', "$1", $post);

        // wrap em blocks in p
        $post = preg_replace('/(<em.*?>(.*?)<\/em>(?:(?!\s*?<em)|\s*?\n\n))/is', '<p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">$1</p>', $post);

        // replace em-s
        $post = preg_replace('/<em.*?>(.*?)<\/em>/is', '<i style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">$1</i><br>', $post);

        // replace captions
        $post = preg_replace('/\[caption.*?\].*?src="(.*?)".*?\/>(.*?)\[\/caption\]/is', $captionTemplate, $post);

        // replace hrefs
        $post = preg_replace('/<a.*?href="(.*?)".*?>(.*?)<\/a>/is', '<a href="$1" title="$2" style="color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;color:#F26755;text-decoration:none;">$2</a>', $post);

        // replace h2
        $post = preg_replace('/<h2.*?>(.*?)<\/h2>/is', '<h2 style="color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-weight:bold;text-align:left;margin-bottom:30px;Margin-bottom:30px;font-size:24px;">$1</h2>' . PHP_EOL, $post);

        // replace images
        $post = preg_replace('/<img.*?src="(.*?)".*?\/>/is', '<img src="$1" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;">', $post);

        // replace ul & /ul
        $post = str_replace('<ul>', '<table style="border-spacing:0;border-collapse:collapse;vertical-align:top;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-align:left;font-family:\'Helvetica Neue\', Helvetica, Arial;width:100%;">
    <tbody>', $post);
        $post = str_replace('</ul>', '</tbody></table>' . PHP_EOL, $post);

        // replace li
        $post = preg_replace('/<li>(.*?)<\/li>/is', $liTemplate, $post);
        // hr
        $post = preg_replace('(<hr>|<hr />)', $hrTemplate, $post);

        // loop through lines and wrap or parse individually
        foreach (preg_split("#\n\s*\n#Uis", $post) as $line) {
            if (empty($line)) {
                continue;
            }

            // parse embedd links
            if (preg_match('/^\s*(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?\s*$/i', $line)) {
                $html .= $this->parseEmbedd(trim($line));
            } else if (// dont wrap line in paragraphs <p> if:
                // not in table
                preg_match('/^\s*?<table/i', $line) ||
                // not in p
                preg_match('/^\s*?<p/i', $line) ||
                // skip lines where is only image tag
                preg_match('/^\s*?<img.*?>\s*?$/i', $line) ||
                // skip lines where is only h2/h3 tag
                preg_match('/^\s*?<(h2|h3).*?\/(h2|h3)>\s*?$/i', $line)
            ) {
                $html .= $line;

            // wrap line in paragraph
            } else {
                $html .= '<p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">';
                $html .= $line;
                $html .= '</p>';
            }
        }

        $loader = new \Twig_Loader_Array([
            'html_template' => $sourceTemplate->content_html,
            'text_template' => $sourceTemplate->content_text,
        ]);
        $twig = new \Twig_Environment($loader);

        $params = [
            'title' => $values->title,
            'sub_title' => $values->sub_title,
            'url' => $values->url,
            'html' => $html,
            'text' => strip_tags($html),
        ];

        $output = [];
        $output['htmlContent'] = $twig->render('html_template', $params);
        $output['textContent'] = $twig->render('text_template', $params);
        return $output;
    }

    public function formSucceeded($form, $values)
    {
        $output = $this->process($values);

        $addonParams = [
            'mediaBriefingTitle' => $values->title,
            'render' => true
        ];

        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
    }

    public function generateForm(Form $form)
    {
        // disable CSRF protection as external sources could post the params here
        $form->offsetUnset(Form::PROTECTOR_ID);

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");
        $form->offsetUnset(Form::PROTECTOR_ID);

        $form->addText('sub_title', 'Sub title')
            ->setRequired("Field 'Sub title' is required.");

        $form->addText('url', 'Media Briefing URL')
            ->addRule(Form::URL)
            ->setRequired("Field 'Media Briefing URL' is required.");

        $form->addTextArea('mediabriefing_html', 'HTML')
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

    private function getLockedHtml($fullHtml, $mediabriefingLink)
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
    href="{$mediafilterLink}/{{ autologin }}">
    Pokračovanie MediaBrífingu - kliknite sem
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


    /**
     * @param $data object containing WP article data
     *
     * @return object with data to fill the form with
     * @throws \Remp\MailerModule\Api\v1\Handlers\Mailers\PreprocessException
     */
    public function preprocessParameters($data)
    {
        $output = new \stdClass();

        if (!isset($data->post_authors[0]->display_name)) {
            throw new PreprocessException("WP json object does not contain required attribute 'display_name' of first post author");
        }

        if (!isset($data->post_title)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_title'");
        }
        $output->title = $data->post_title;

        if (!isset($data->post_url)) {
            throw new PreprocessException("WP json object  does not contain required attribute 'post_url'");
        }
        $output->url = $data->post_url;

        if (!isset($data->post_excerpt)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_excerpt'");
        }

        if (!isset($data->post_content)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_content'");
        }
        $output->mediabriefing_html = $data->post_content;

        return $output;
    }

    public function parseEmbedd($link)
    {
        if (preg_match('/youtube/', $link)) {
            $result = (new Client())->get('https://www.youtube.com/oembed?url=' . $link . '&format=json')->getBody()->getContents();
            $result = json_decode($result);
            $thumbLink = $result->thumbnail_url;

            return "<a href=\"{$link}\" target=\"_blank\" style=\"color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;color:#F26755;text-decoration:none;\"><img src=\"{$thumbLink}\" alt=\"\" style=\"outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;\"></a><br><br>" . PHP_EOL;
        }

        return "<a href=\"{$link}\" target=\"_blank\" style=\"color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;color:#F26755;text-decoration:none;\">$link</a><br><br>";
    }

    public function getTemplates()
    {
        $captionTemplate = <<< HTML
    <img src="$1" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;">
    <p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">
        <small class="text-gray" style="font-size:13px;line-height:18px;display:block;color:#9B9B9B;">$2</small>
    </p>
HTML;

        $liTemplate = <<< HTML
    <tr style="padding:0;vertical-align:top;text-align:left;">
        <td class="bullet" style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;width:30px;border-collapse:collapse !important;">&#8226;</td>
        <td style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;border-collapse:collapse !important;">
            <p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">$1</p>
        </td>
    </tr>
HTML;

        $hrTemplate = <<< HTML
    <table cellspacing="0" cellpadding="0" border="0" width="100%" style="border-spacing:0;border-collapse:collapse;vertical-align:top;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-align:left;font-family:'Helvetica Neue', Helvetica, Arial;width:100%;">
        <tr style="padding:0;vertical-align:top;text-align:left;">
            <td style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;border-collapse:collapse !important; padding: 30px 0 0 0; border-top:1px solid #E2E2E2;"></td>
        </tr>
    </table>

HTML;

        return [
            $captionTemplate,
            $liTemplate,
            $hrTemplate,
        ];
    }
}
