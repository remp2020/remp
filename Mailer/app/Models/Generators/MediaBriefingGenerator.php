<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\MediaBriefingWidget\MediaBriefingWidget;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\HtmlArticleLocker;
use Remp\MailerModule\Models\Generators\EmbedParser;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Models\Generators\PreprocessException;
use Remp\MailerModule\Models\Generators\WordpressHelpers;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use GuzzleHttp\Client;
use Tomaj\NetteApi\Params\PostInputParam;

class MediaBriefingGenerator implements IGenerator
{
    use RulesTrait, TemplatesTrait;

    public $onSubmit;

    private $mailSourceTemplateRepository;

    private $helpers;

    private $content;

    private $linksColor = "#1F3F83";

    private $embedParser;

    private $articleLocker;

    private $engineFactory;

    public function __construct(
        SourceTemplatesRepository $mailSourceTemplateRepository,
        WordpressHelpers          $helpers,
        ContentInterface          $content,
        EmbedParser               $embedParser,
        HtmlArticleLocker         $articleLocker,
        EngineFactory             $engineFactory
    ) {
        $this->mailSourceTemplateRepository = $mailSourceTemplateRepository;
        $this->helpers = $helpers;
        $this->content = $content;
        $this->embedParser = $embedParser;
        $this->articleLocker = $articleLocker;
        $this->engineFactory = $engineFactory;
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('mediabriefing_html'))->setRequired(),
            (new PostInputParam('url'))->setRequired(),
            (new PostInputParam('title'))->setRequired(),
            (new PostInputParam('sub_title')),
            (new PostInputParam('image_url')),
            (new PostInputParam('image_title')),
            (new PostInputParam('from'))->setRequired(),
        ];
    }

    public function getWidgets(): array
    {
        return [MediaBriefingWidget::class];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->mailSourceTemplateRepository->find($values['source_template_id']);

        $post = $values['mediabriefing_html'];
        $lockedPost = $this->articleLocker->getLockedPost($post);

        $generatorRules = [
            '/<p.*?>(.*?)<\/p>/is' => "<p style=\"color:#181818;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight:normal;padding:0;margin:0 0 26px 0;text-align:left;font-size:18px;line-height:160%;\">$1</p>",
        ];
        $rules = $this->getRules($generatorRules);

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

        // parse article links
        $post = $this->helpers->wpParseArticleLinks($post, 'https://dennikn.sk/', $this->getArticleLinkTemplateFunction());
        $lockedPost = $this->helpers->wpParseArticleLinks($lockedPost, 'https://dennikn.sk/', $this->getArticleLinkTemplateFunction());

        // fix pees
        [$post, $lockedPost] = preg_replace('/<p>/is', "<p style=\"color:#181818;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight:normal;padding:0;margin:0 0 26px 0;text-align:left;font-size:18px;line-height:160%;\">", [$post, $lockedPost]);

        [$captionTemplate,,,,,$imageTemplate] = $this->getTemplates();
        $imageHtml = '';

        if (isset($values['image_title']) && isset($values['image_url'])) {
            $imageHtml = str_replace('$1', $values['image_url'], $captionTemplate);
            $imageHtml = str_replace('$2', $values['image_title'], $imageHtml);
        } elseif (isset($values['image_url'])) {
            $imageHtml = str_replace('$1', $values['image_url'], $imageTemplate);
        }

        $post = $imageHtml . $post;
        $lockedPost = $imageHtml . $lockedPost;

        $text = str_replace("<br />", "\r\n", $post);
        $lockedText = str_replace("<br />", "\r\n", $lockedPost);

        $lockedPost = $this->articleLocker->injectLockedMessage($lockedPost);

        $text = strip_tags($text);
        $lockedText = strip_tags($lockedText);

        $text = preg_replace('/(\r\n|\r|\n)+/', "\n", $text);
        $lockedText = preg_replace('/(\r\n|\r|\n)+/', "\n", $lockedText);

        $params = [
            'title' => $values['title'],
            'sub_title' => $values['sub_title'],
            'url' => $values['url'],
            'html' => $post,
            'text' => $text,
        ];

        $lockedParams = [
            'title' => $values['title'],
            'sub_title' => $values['sub_title'],
            'url' => $values['url'],
            'html' => $lockedPost,
            'text' => strip_tags($lockedText),
        ];

        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
            'lockedHtmlContent' => $engine->render($sourceTemplate->content_html, $lockedParams),
            'lockedTextContent' => strip_tags($engine->render($sourceTemplate->content_text, $lockedParams)),
        ];
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $output = $this->process((array) $values);

        $addonParams = [
            'lockedHtmlContent' => $output['lockedHtmlContent'],
            'lockedTextContent' => $output['lockedTextContent'],
            'mediaBriefingTitle' => $values->title,
            'from' => $values->from,
            'render' => true,
            'articleId' => $values->article_id,
        ];

        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
    }

    public function generateForm(Form $form): void
    {
        // disable CSRF protection as external sources could post the params here
        $form->offsetUnset(Form::PROTECTOR_ID);

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");
        $form->offsetUnset(Form::PROTECTOR_ID);

        $form->addText('sub_title', 'Sub title');

        $form->addText('from', 'Sender');

        $form->addText('url', 'Media Briefing URL')
            ->addRule(Form::URL)
            ->setRequired("Field 'Media Briefing URL' is required.");

        $form->addTextArea('mediabriefing_html', 'HTML')
            ->setHtmlAttribute('rows', 20)
            ->setHtmlAttribute('class', 'form-control html-editor')
            ->getControlPrototype();

        $form->addHidden('article_id');

        $form->addSubmit('send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-magic"></i> ' . 'Generate');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    /**
     * @param \stdClass $data containing WP article data
     * @return ArrayHash with data to fill the form with
     */
    public function preprocessParameters($data): ?ArrayHash
    {
        $output = new ArrayHash();

        if (!isset($data->post_authors[0]->display_name)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_authors.0.display_name'");
        }

        if (isset($data->sender_email) && $data->sender_email) {
            $output->from = $data->sender_email;
        } else {
            $output->from = "Denník N <info@dennikn.sk>";
            foreach ($data->post_authors as $author) {
                if ($author->user_email === "editori@dennikn.sk") {
                    continue;
                }
                $output->from = $author->display_name . ' Denník N <' . $author->user_email . '>';
                break;
            }
        }

        if (!isset($data->post_title)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_title'");
        }
        $output->title = $data->post_title;

        if (!isset($data->post_url)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_url'");
        }
        $output->url = $data->post_url;

        if (!isset($data->post_excerpt)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_excerpt'");
        }
        $output->sub_title = $data->post_excerpt;

        if (isset($data->post_image->image_sizes->medium->file)) {
            $output->image_url = $data->post_image->image_sizes->medium->file;
        }

        if (isset($data->post_image->image_title)) {
            $output->image_title = $data->post_image->image_title;
        }

        if (!isset($data->post_content)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_content'");
        }
        $output->mediabriefing_html = $data->post_content;

        $output->article_id = $data->ID;

        return $output;
    }

    public function parseEmbed($matches)
    {
        $link = trim($matches[0]);

        if (preg_match('/youtu/', $link)) {
            $result = (new Client())->get('https://www.youtube.com/oembed?url=' . $link . '&format=json')->getBody()->getContents();
            $result = json_decode($result);
            $thumbLink = $result->thumbnail_url;

            return "<br><a href=\"{$link}\" target=\"_blank\" style=\"color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;color:#1F3F83;text-decoration:underline;\"><img src=\"{$thumbLink}\" alt=\"\" style=\"outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;\"></a><br><br>" . PHP_EOL;
        }

        return "<a href=\"{$link}\" target=\"_blank\" style=\"color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;color:#1F3F83;text-decoration:underline;\">$link</a><br><br>";
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
    <a href="$1" style="color:#181818;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight:normal;padding:0;margin:0;Margin:0;text-align:left;line-height:1.3;color:#1F3F83;text-decoration:none;">
    <img src="$2" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;border:none;">
</a>
    <p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">
        <small class="text-gray" style="font-size:13px;line-height:18px;display:block;color:#9B9B9B;">$3</small>
    </p>
HTML;

        $liTemplate = <<< HTML
    <tr style="padding:0;vertical-align:top;text-align:left;">
        <td class="bullet" style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;width:30px;border-collapse:collapse !important;">&#8226;</td>
        <td style="padding:0;vertical-align:top;text-align:left;font-size:18px;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;border-collapse:collapse !important;">
            <p style="color:#181818;padding:0;margin:0 0 5px 0;font-size:18px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">$1</p>
        </td>
    </tr>
HTML;

        $hrTemplate = <<< HTML
    <table cellspacing="0" cellpadding="0" border="0" width="100%" style="border-spacing:0;border-collapse:collapse;vertical-align:top;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-align:left;font-family:'Helvetica Neue', Helvetica, Arial;width:100%;min-width:100%;">
        <tr style="padding:0;vertical-align:top;text-align:left;width:100%;min-width:100%;">
            <td style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;border-collapse:collapse !important; padding: 30px 0 0 0; border-top:1px solid #E2E2E2;height:0;line-height: 0;width:100%;min-width:100%;">&#xA0;</td>
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
