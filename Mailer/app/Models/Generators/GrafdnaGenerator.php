<?php

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Http\Url;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\GrafdnaWidget\GrafdnaWidget;
use Remp\Mailer\Models\WebClient;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\EmbedParser;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Models\Generators\PreprocessException;
use Remp\MailerModule\Models\Generators\WordpressHelpers;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class GrafdnaGenerator implements IGenerator
{
    use RulesTrait, TemplatesTrait, WordpressBlocksTrait;

    public $onSubmit;

    public function __construct(
        private readonly SourceTemplatesRepository $mailSourceTemplateRepository,
        private readonly WordpressHelpers $helpers,
        private readonly ContentInterface $content,
        private readonly EmbedParser $embedParser,
        private readonly N3ArticleLocker $articleLocker,
        private readonly EngineFactory $engineFactory,
        private readonly WebClient $webClient,
        private readonly SnippetsRepository $snippetsRepository,
        private readonly TransportInterface $transport,
    ) {
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('grafdna_html'))->setRequired(),
            (new PostInputParam('url'))->setRequired(),
            (new PostInputParam('image_url')),
            (new PostInputParam('title'))->setRequired(),
            (new PostInputParam('from'))->setRequired(),
            (new PostInputParam('editor'))->setRequired(),
            (new PostInputParam('editor_avatar_url')),
            (new PostInputParam('summary')),
        ];
    }

    public function generateForm(Form $form): void
    {
        // disable CSRF protection as external sources could post the params here
        $form->offsetUnset(Form::ProtectorId);

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addText('url', 'Newsfilter URL')
            ->addRule(Form::URL)
            ->setRequired("Field 'Newsfilter URL' is required.");

        $form->addText('image_url', 'Image URL')
            ->setNullable()
            ->addRule(Form::URL);

        $form->addText('from', 'Sender');

        $form->addText('editor', 'Editor')
            ->setRequired("Field 'Editor' is required.");

        $form->addText('editor_avatar_url', 'Editor avatar URL');

        $form->addTextArea('summary', 'Summary')
            ->setHtmlAttribute('rows', 3)
            ->setRequired(false);

        $form->addTextArea('grafdna_html', 'HTML')
            ->setHtmlAttribute('rows', 20)
            ->setHtmlAttribute('class', 'form-control html-editor')
            ->getControlPrototype();

        $form->addHidden('article_id')->setNullable();

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

    public function getWidgets(): array
    {
        return [GrafdnaWidget::class];
    }

    public function process(array $values): array
    {
        $this->articleLocker->setLockText('Predplaťte si Denník E a tento newsletter dostanete celý.');
        $this->articleLocker->setLockLink('Pridajte sa k predplatiteľom', 'https://predplatne.dennikn.sk/ecko');

        $errors = [];

        $post = $this->preprocessBlocks($values['grafdna_html']);
        $post = $this->parseOls($post);

        $lockedPost = $this->articleLocker->getLockedPost($post);

        if (!empty($values['image_url'])) {
            // match first embed or graph URL in text and replace with provided image
            $specialRule = [
                "/(\[embed\](.*?)\[\/embed\]|^(http|https)\:\/\/[a-zA-Z0-9\-\.]*(flourish|datawrapper)+[a-zA-Z0-9\-\.]*\.[a-zA-Z]+(\/\S*)?\s*$)/im" => function ($matches) use ($values) {
                    $link = null;
                    foreach ($matches as $match) {
                        if (Strings::startsWith($match, 'http')) {
                            $link = $match;
                            break;
                        }
                    }

                    if (isset($link)) {
                        return <<< HTML
<img src={$values['image_url']} alt="" style="width: 100%"/>
<p>Graf nájdete aj na <a href="$link">$link</a>.</p>
HTML;
                    }
                    return '';
                }
            ];
            $post = preg_replace_callback(key($specialRule), current($specialRule), $post, 1);
            $lockedPost = preg_replace_callback(key($specialRule), current($specialRule), $lockedPost, 1);
        }

        $generatorRules = [
            '/<h2.*?>.*?\*.*?<\/h2>/im' => '<div style="color:#181818;padding:0;line-height:1.3;font-weight:bold;text-align:center;margin:0 0 30px 0;font-size:24px;">*</div>',
            '/<p.*?>(.*?)<\/p>/is' => "<p style=\"color:#181818;font-family:Georgia,sans-serif;font-weight:400;padding:0;text-align:left;font-size:18px;line-height:27px;margin: 16px 0 16px 0\">$1</p>",
            "/<blockquote.*?>(.*?)<\/blockquote>/is" => '<blockquote style="position: relative;padding: 16px;border-radius: 6px;font-style: normal; background: #F2EAE8; margin: 0 0 16px 0">$1</blockquote>',
            "/https:\/\/dennikn\.podbean\.com\/e\/.*?[\s\n\r]/is" => "",
            '/<img.*?src="(.*?)".*?>/is' => function ($matches) {
                $src = $matches[1];
                $width = 564;
                $maxWidth = '100%';
                if (preg_match('/width="(\d+)"/i', $matches[0], $widthMatches)) {
                    $imgWidth = (int) $widthMatches[1];
                    if ($imgWidth < 564) {
                        $width = $imgWidth;
                        $maxWidth = $imgWidth . 'px';
                    }
                }
                return '<img src="' . $src . '" alt="" width="' . $width . '" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:100%;max-width:' . $maxWidth . ';clear:both;display:block;margin-bottom:20px;">';
            },
        ];
        $rules = $this->getRules($generatorRules);

        // prepend greybox before rules remove all DIVs.
        $rules = array_merge([
            '/<div class="t_greybox">(.*?)<\/div>/is' => $this->getGreyboxTemplate(),
        ], $rules);

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

        [$post, $lockedPost] = preg_replace('/<p>/is', "<p style=\"color:#181818;font-family:Georgia,sans-serif;font-weight:normal;padding:0;text-align:left;font-size:18px;line-height:27px;margin: 16px 0 16px 0\">", [$post, $lockedPost]);

        $lockedPost = $this->articleLocker->injectLockedMessage($lockedPost);

        $economyPostsResponse = $this->webClient->getEconomyPostsLast24Hours();
        $excerpt = $economyPostsResponse['meta']['excerpt'];

        $economyPosts = $this->filterPosts($economyPostsResponse['posts']);
        $economyPosts = array_slice($economyPosts, 0, 5);
        foreach ($economyPosts as &$economyPost) {
            $economyPost['image_url'] = $this->getImageUrlForPost($economyPost);
        }

        $adSnippet = $this->snippetsRepository->all()->where([
            'code' => 'ad-grafdna',
            'html <> ?' => '',
            'mail_type_id' => null,
        ])->fetch();

        $params = [
            'title' => $values['title'],
            'url' => $values['url'],
            'image_url' => $values['image_url'],
            'editor' => $values['editor'],
            'editor_avatar_url' => $values['editor_avatar_url'] ?? null,
            'summary' => $values['summary'] ?? null,
            'html' => $post,
            'text' => strip_tags($post),
            'excerpt' => $excerpt,
            'excerptText' => strip_tags($excerpt),
            'economyPosts' => $economyPosts,
            'adSnippetHtml' => $adSnippet?->html,
            'adSnippetText' => $adSnippet?->text,
        ];
        $lockedParams = [
            'title' => $values['title'],
            'url' => $values['url'],
            'image_url' => $values['image_url'],
            'editor' => $values['editor'],
            'editor_avatar_url' => $values['editor_avatar_url'] ?? null,
            'summary' => $values['summary'] ?? null,
            'html' => $lockedPost,
            'text' => strip_tags($lockedPost),
            'excerpt' => $excerpt,
            'excerptText' => strip_tags($excerpt),
            'economyPosts' => $economyPosts,
            'adSnippetHtml' => $adSnippet?->html,
            'adSnippetText' => $adSnippet?->text,
        ];

        $sourceTemplate = $this->mailSourceTemplateRepository->find($values['source_template_id']);
        $engine = $this->engineFactory->engine();

        $params['html'] = $engine->markSafe($params['html']);
        $params['text'] = $engine->markSafe($params['text']);
        $lockedParams['html'] = $engine->markSafe($lockedParams['html']);
        $lockedParams['text'] = $engine->markSafe($lockedParams['text']);

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
            'grafdnaTitle' => $values->title,
            'from' => $values->from,
            'render' => true,
            'articleId' => $values->article_id,
            'errors' => $output['errors'],
        ];

        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
    }

    /**
     * @param \stdClass $data containing WP article data
     * @return ArrayHash with data to fill the form with
     */
    public function preprocessParameters($data): ?ArrayHash
    {
        $output = new ArrayHash();

        $output->from = "Denník E <e@dennikn.sk>";

        if (!isset($data->post_authors[0]->display_name)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_authors.0.display_name'");
        }
        $output->editor = $data->post_authors[0]->display_name;
        $output->editor_avatar_url = $data->post_authors[0]->avatar_url;

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
        $output->grafdna_html = $data->post_content;

        // remp/remp#1174
        // og:image is used as graph image instead of complex graph element in generated newsletter
        // this image contains labels and logo
        $imageUrl = $this->getSocialImageUrl($data->post_url);
        if (!$imageUrl) {
            $meta = $this->content->fetchUrlMeta($data->post_url);
            $imageUrl = $meta->getImage();
        }

        $output->image_url = (new Url($imageUrl))->setQuery([])->getAbsoluteUrl();

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
    <a href="$1" style="color:#181818;font-family:Georgia,sans-serif;font-weight:normal;padding:0;margin:0;Margin:0;text-align:left;line-height:1.3;color:{$this->getLinksColor()};text-decoration:none;">
    <img src="$2" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:20px;border:none;">
</a>
    <p style="margin:0 0 0 26px;Margin:0 0 0 26px;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-size:18px;line-height:1.6;margin-bottom:26px;Margin-bottom:26px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">
        <small class="text-gray" style="font-size:13px;line-height:18px;display:block;color:#9B9B9B;">$3</small>
    </p>
HTML;

        $liTemplate = <<< HTML
    <tr style="padding:0;vertical-align:top;text-align:left;">
        <td class="bullet" style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;width:30px;border-collapse:collapse !important;">&#8226;</td>
        <td style="padding:0;vertical-align:top;text-align:left;font-size:18px;font-family:Georgia,sans-serif;border-collapse:collapse !important;">
            <p style="color:#181818;padding:0;margin:0 0 5px 0;font-size:18px;line-height:160%;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">$1</p>
        </td>
    </tr>
HTML;

        $hrTemplate = <<< HTML
    <table cellspacing="0" cellpadding="0" border="0" width="100%" style="border-spacing:0;border-collapse:collapse;vertical-align:top;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-align:left;font-family:Georgia,sans-serif;width:100%;min-width:100%;">
        <tr style="padding:0;vertical-align:top;text-align:left;width:100%;min-width:100%;">
            <td style="padding:0;vertical-align:top;text-align:left;font-size:18px;line-height:1.6;border-collapse:collapse !important; padding: 30px 0 0 0; border-top:1px solid #E2E2E2;height:0;line-height: 0;width:100%;min-width:100%;">&#xA0;</td>
        </tr>
    </table>

HTML;

        $spacerTemplate = <<< HTML
        <table class="spacer" style="border-spacing:0;border-collapse:collapse;vertical-align:top;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-align:left;font-family:Georgia,sans-serif;width:100%;">
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

    private function getGreyboxTemplate(): string
    {
        return <<<HTML
<table role="presentation" width="600" style="width: 100%; max-width: 600px; border-spacing:0; border-collapse:collapse; vertical-align:top; background-color:#F2EAE8; padding:0; text-align:left; margin:0;">
    <tbody>
        <tr>
            <td style="padding: 30px 24px;">$1</td>
        </tr>
    </tbody>
</table>
HTML;
    }

    public function parseOls($post)
    {
        return preg_replace_callback('/<ol([^>]*)>(.*?)<\/ol>/is', function ($matches) {
            $olsLis = [];
            preg_match_all('/<li[^>]*>(?:<p.*?>)?(.*?)(?:<\/p>)?<\/li>/is', $matches[2], $olsLis);
            if (!$olsLis[1]) {
                return $matches[0];
            }

            $liNum = preg_match('/\bstart="?(\d+)"?/i', $matches[1], $start) ? (int) $start[1] : 1;

            $items = [];
            foreach ($olsLis[1] as $liContent) {
                $items[] = '<span style="color:#AE0D21;">' . $liNum . '.</span> ' . trim($liContent);
                $liNum++;
            }

            return PHP_EOL . '<p>' . implode('<br>', $items) . '</p>' . PHP_EOL;
        }, $post);
    }

    private function filterPosts($posts): array
    {
        $tagsToFilter = ['graf-dna', 'ekonomicky-newsfilter'];

        return array_filter($posts, function ($post) use ($tagsToFilter) {
            foreach ($post['tags'] as $tag) {
                if (in_array($tag['slug'], $tagsToFilter, true)) {
                    return false;
                }
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function getImageUrlForPost($post)
    {
        $desiredWidth = 500;
        $currentDiff = null;
        $currentUrl = null;

        foreach ($post['image']['sizes'] as $image) {
            $diff = abs($image['width'] - $desiredWidth);
            if (!isset($currentDiff) || $diff < $currentDiff) {
                $currentDiff = $diff;
                $currentUrl = $image['url'];
            }
        }

        return $currentUrl;
    }

    private function getSocialImageUrl($postUrl)
    {
        $content = $this->transport->getContent($postUrl);
        $matches = [];
        preg_match('/<meta property=\"og:image\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            return $matches[1];
        }

        return null;
    }
}
