<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\NewsfilterWidget\NewsfilterWidget;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\HtmlArticleLocker;
use Remp\MailerModule\Models\Generators\EmbedParser;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Models\Generators\PreprocessException;
use Remp\MailerModule\Models\Generators\WordpressHelpers;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class NewsfilterGenerator implements IGenerator
{
    use RulesTrait, TemplatesTrait;

    public $onSubmit;

    private $mailSourceTemplateRepository;

    private $helpers;

    private $content;

    private $embedParser;

    protected $articleLocker;

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
            (new PostInputParam('newsfilter_html'))->setRequired(),
            (new PostInputParam('url'))->setRequired(),
            (new PostInputParam('title'))->setRequired(),
            (new PostInputParam('editor'))->setRequired(),
            (new PostInputParam('summary')),
            (new PostInputParam('from'))->setRequired(),
        ];
    }

    public function getWidgets(): array
    {
        return [NewsfilterWidget::class];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->mailSourceTemplateRepository->find($values['source_template_id']);

        $post = $values['newsfilter_html'];
        $post = $this->parseOls($post);

        $lockedPost = $this->articleLocker->getLockedPost($post);
        $errors = [];

        $generatorRules = [
            '/<h2.*?>.*?\*.*?<\/h2>/im' => '<div style="color:#181818;padding:0;line-height:1.3;font-weight:bold;text-align:center;margin:0 0 30px 0;font-size:24px;">*</div>',
            "/https:\/\/dennikn\.podbean\.com\/e\/.*?[\s\n\r]/is" => "",
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
        $post = $this->helpers->wpParseArticleLinks($post, 'https://dennikn.sk/', $this->getArticleLinkTemplateFunction(), $errors);
        $lockedPost = $this->helpers->wpParseArticleLinks($lockedPost, 'https://dennikn.sk/', $this->getArticleLinkTemplateFunction(), $errors);

        [$post, $lockedPost] = preg_replace('/<p>/is', "<p style=\"color:#181818;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight:normal;padding:0;text-align:left;font-size:18px;line-height:160%;margin: 16px 0 16px 0\">", [$post, $lockedPost]);

        $lockedPost = $this->articleLocker->injectLockedMessage($lockedPost);

        $params = [
            'title' => $values['title'],
            'editor' => $values['editor'],
            'summary' => $values['summary'],
            'url' => $values['url'],
            'html' => $post,
            'text' => strip_tags($post),
            'locked' => false,
        ];
        $lockedParams = [
            'title' => $values['title'],
            'editor' => $values['editor'],
            'summary' => $values['summary'],
            'url' => $values['url'],
            'html' => $lockedPost,
            'text' => strip_tags($lockedPost),
            'locked' => true,
        ];

        $engine = $this->engineFactory->engine();
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
            'lockedHtmlContent' => $engine->render($sourceTemplate->content_html, $lockedParams),
            'lockedTextContent' => strip_tags($engine->render($sourceTemplate->content_text, $lockedParams)),
            'errors' => $errors,
        ];
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $output = $this->process((array) $values);

        $addonParams = [
            'lockedHtmlContent' => $output['lockedHtmlContent'],
            'lockedTextContent' => $output['lockedTextContent'],
            'newsfilterTitle' => $values->title,
            'from' => $values->from,
            'render' => true,
            'articleId' => $values->article_id,
            'errors' => $output['errors'],
        ];

        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
    }

    public function generateForm(Form $form): void
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
            ->setHtmlAttribute('rows', 3)
            ->setRequired(false);

        $form->addTextArea('newsfilter_html', 'HTML')
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
        $output->editor = $data->post_authors[0]->display_name;

        if (isset($data->sender_email) && $data->sender_email) {
            $output->from = $data->sender_email;
        } else {
            $output->from = "Denník N <info@dennikn.sk>";
            foreach ($data->post_authors as $author) {
                if ($author->user_email === "editori@dennikn.sk") {
                    continue;
                }

                if ($author->user_email !== 'e@dennikn.sk') {
                    $output->from = $author->display_name . ' Denník N <' . $author->user_email . '>';
                } else {
                    $output->from = $author->display_name . ' <' . $author->user_email . '>';
                }
                break;
            }
        }

        foreach ($data->post_authors as $author) {
            if ($author->user_email === "editori@dennikn.sk") {
                continue;
            }

            $output->editor = $author->display_name;
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

        $output->article_id = $data->ID;

        return $output;
    }

    public function getTemplates(): array
    {
        $captionTemplate = <<< HTML
    <img src="$1" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;">
    <p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">
        <small class="text-gray" style="font-size:13px;line-height:18px;display:block;color:#9B9B9B;">$2</small>
    </p>
HTML;

        $captionWithLinkTemplate = <<< HTML
    <a href="$1" style="color:#181818;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight:normal;padding:0;margin:0;Margin:0;text-align:left;line-height:1.3;color:{$this->linksColor};text-decoration:none;">
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

    public function parseOls($post)
    {
        $ols = [];
        preg_match_all('/<ol.*?>(.*?)<\/ol>/is', $post, $ols);

        foreach ($ols[1] as $olContent) {
            $olsLis = [];
            $liNum = 1;
            $newOlContent = '';

            preg_match_all('/<li>(.*?)<\/li>/is', $olContent, $olsLis);


            foreach ($olsLis[1] as $liContent) {
                $newOlContent .= '
    <tr style="padding:0;vertical-align:top;text-align:left;">
        <td class="bullet" style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;width:30px;border-collapse:collapse !important;">' . $liNum . '</td>
        <td style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;border-collapse:collapse !important;">
            <p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">' . $liContent . '</p>
        </td>
    </tr>';

                $liNum++;
            }

            $post = str_replace($olContent, $newOlContent, $post);
        }

        return $post;
    }
}
