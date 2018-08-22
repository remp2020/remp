<?php

namespace Remp\MailerModule\Generators;

use GuzzleHttp\Client;
use Nette\Application\UI\Form;
use Remp\MailerModule\Api\v1\Handlers\Mailers\PreprocessException;
use Remp\MailerModule\Components\GeneratorWidgets\Widgets\NewsfilterWidget;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\InputParam;

class NewsfilterGenerator implements IGenerator
{
    protected $mailSourceTemplateRepository;

    public $onSubmit;

    public $helpers;

    public function __construct(
        SourceTemplatesRepository $mailSourceTemplateRepository,
        WordpressHelpers $helpers
    )
    {
        $this->mailSourceTemplateRepository = $mailSourceTemplateRepository;
        $this->helpers = $helpers;
    }

    public function apiParams()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'newsfilter_html', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'url', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'title', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'editor', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'summary', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'from', InputParam::REQUIRED),
        ];
    }

    public function getWidgets()
    {
        return [NewsfilterWidget::class];
    }

    public function process($values)
    {
        $sourceTemplate = $this->mailSourceTemplateRepository->find($values->source_template_id);

        $post = $values->newsfilter_html;
        $lockedPost = $this->getLockedHtml($values->newsfilter_html, $values->url);

        list(
            $captionTemplate,
            $captionWithLinkTemplate,
            $liTemplate,
            $hrTemplate,
            $spacerTemplate,
            $imageTemplate
        ) = $this->getTemplates();


        $rules = [
            // remove shortcodes
            "/\[greybox\]/is" => "",
            "/\[\/greybox\]/is" => "",
            "/https:\/\/dennikn\.podbean\.com\/e\/.*?[\s\n\r]/is" => "",
            "/\[pullboth.*?\/pullboth\]/is" => "",
            "/<script.*?\/script>/is" => "",
            "/\[iframe.*?\]/is" => "",
            '/\[\/?lock\]/i' => "",
            '/\[lock newsletter\]/i' => "",
            '/\[lock\]/i' => "",

            // remove iframes
            "/<iframe.*?\/iframe>/is" => "",

            // remove paragraphs
            '/<p.*?>(.*?)<\/p>/is' => "$1",

            // replace em-s
            "/<em.*?>(.*?)<\/em>/is" => "<i style=\"margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;\">$1</i><br>",

            // remove new lines from inside caption shortcode
            "/\[caption.*?\/caption\]/is" => function ($matches) {
                return str_replace(array("\n\r", "\n", "\r"), '', $matches[0]);
            },

            // replace captions
            '/\[caption.*?\].*?href="(.*?)".*?src="(.*?)".*?\/a>(.*?)\[\/caption\]/im' => $captionWithLinkTemplate,
            '/\[caption.*?\].*?src="(.*?)".*?\/>(.*?)\[\/caption\]/im' => $captionTemplate,

            // replace link shortcodes
            '/\[articlelink.*?id="(.*?)".*?]/is' => array($this->helpers, "parseArticleLink"),

            // replace hrefs
            '/<a.*?href="(.*?)".*?>(.*?)<\/a>/is' => '<a href="$1" style="color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;color:#F26755;text-decoration:none;">$2</a>',

            // replace h2
            '/<h2.*?>(.*?)<\/h2>/is' => '<h2 style="color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-weight:bold;text-align:left;margin-bottom:30px;Margin-bottom:30px;font-size:24px;">$1</h2>' . PHP_EOL,

            // replace images
            '/<img.*?src="(.*?)".*?>/is' => $imageTemplate,

            // replace ul & /ul
            '/<ul>/is' => '<table style="border-spacing:0;border-collapse:collapse;vertical-align:top;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-align:left;font-family:\'Helvetica Neue\', Helvetica, Arial;width:100%;"><tbody>',

            '/<\/ul>/is' => '</tbody></table>' . PHP_EOL,

            // replace li
            '/<li>(.*?)<\/li>/is' => $liTemplate,

            // hr
            '/(<hr>|<hr \/>)/is' => $hrTemplate,

            // parse embedds
            '/^\s*(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?\s*$/im' => array($this, "parseEmbed"),

            // remove br from inside of a
            '/<a.*?\/a>/is' => function ($matches) {
                return str_replace('<br />', '', $matches[0]);
            }
        ];

        foreach ($rules as $rule => $replace) {
            if (is_array($replace) || is_callable($replace)) {
                $post = preg_replace_callback($rule, $replace, $post);
                $lockedPost = preg_replace_callback($rule, $replace, $lockedPost);
            } else {
                $post = preg_replace($rule, $replace, $post);
                $lockedPost = preg_replace($rule, $replace, $lockedPost);
            }
        }
        // wrap text in paragraphs
        $post = $this->helpers->wpautop($post);
        $lockedPost = $this->helpers->wpautop($lockedPost);

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
            'html' => $post,
            'text' => strip_tags($post),
        ];
        $lockedParams = [
            'title' => $values->title,
            'editor' => $values->editor,
            'url' => $values->url,
            'summary' => $values->summary,
            'html' => $lockedPost,
            'text' => strip_tags($lockedPost),
        ];

        $output = [];
        $output['htmlContent'] = $twig->render('html_template', $params);
        $output['textContent'] = $twig->render('text_template', $params);
        $output['lockedHtmlContent'] = $twig->render('html_template', $lockedParams);
        $output['lockedTextContent'] = $twig->render('text_template', $lockedParams);
        return $output;
    }

    public function formSucceeded($form, $values)
    {
        $output = $this->process($values);

        $addonParams = [
            'lockedHtmlContent' => $output['lockedHtmlContent'],
            'lockedTextContent' => $output['lockedTextContent'],
            'newsfilterTitle' => $values->title,
            'from' => $values->from,
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

        $form->addText('url', 'Newsfilter URL')
            ->addRule(Form::URL)
            ->setRequired("Field 'Newsfilter URL' is required.");

        $form->addText('from', 'Sender');

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
        $output->editor = $data->post_authors[0]->display_name;
        $output->from = "Denník N <info@dennikn.sk>";
        foreach ($data->post_authors as $author) {
            if ($author->user_email === "editori@dennikn.sk") {
                continue;
            }
            $output->editor = $author->display_name;
            $output->from = $author->display_name . ' <' . $author->user_email . '>';
            break;
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
        $output->summary = $data->post_excerpt;

        if (!isset($data->post_content)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_content'");
        }
        $output->newsfilter_html = $data->post_content;

        return $output;
    }

    public function parseEmbed($matches)
    {
        $link = trim($matches[0]);

        if (preg_match('/youtu/', $link)) {
            $result = (new Client())->get('https://www.youtube.com/oembed?url=' . $link . '&format=json')->getBody()->getContents();
            $result = json_decode($result);
            $thumbLink = $result->thumbnail_url;

            return "<br><a href=\"{$link}\" target=\"_blank\" style=\"color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;color:#F26755;text-decoration:none;\"><img src=\"{$thumbLink}\" alt=\"\" style=\"outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;\"></a><br><br>" . PHP_EOL;
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

        $captionWithLinkTemplate = <<< HTML
    <a href="$1" style="color:#181818;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight:normal;padding:0;margin:0;Margin:0;text-align:left;line-height:1.3;color:#F26755;text-decoration:none;">
    <img src="$2" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;border:none;">
</a>
    <p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">
        <small class="text-gray" style="font-size:13px;line-height:18px;display:block;color:#9B9B9B;">$3</small>
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

        $spacerTemplate = <<< HTML
        <table class="spacer" style="border-spacing:0;border-collapse:collapse;vertical-align:top;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-align:left;font-family:'Helvetica Neue', Helvetica, Arial;width:100%;">
            <tbody>
                <tr style="padding:0;vertical-align:top;text-align:left;">
                    <td height="20px" style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;mso-line-height-rule:exactly;border-collapse:collapse !important;font-size:20px;line-height:20px;">&#xA0;</td>
                </tr>
            </tbody>
        </table>
HTML;

        $imageTemplate = <<< HTML
        <img src="$1" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;">
HTML;

        return [
            $captionTemplate,
            $captionWithLinkTemplate,
            $liTemplate,
            $hrTemplate,
            $spacerTemplate,
            $imageTemplate,
        ];
    }
}
