<?php

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Http\Url;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\GrafdnaWidget\GrafdnaWidget;
use Remp\Mailer\Models\WebClient;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\HtmlArticleLocker;
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
    use RulesTrait, TemplatesTrait;

    public $onSubmit;

    public function __construct(
        private SourceTemplatesRepository $mailSourceTemplateRepository,
        private WordpressHelpers          $helpers,
        private ContentInterface          $content,
        private EmbedParser               $embedParser,
        private HtmlArticleLocker         $articleLocker,
        private EngineFactory             $engineFactory,
        private WebClient                 $webClient,
        private SnippetsRepository        $snippetsRepository,
        private TransportInterface        $transport,
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
        ];
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

        $form->addText('image_url', 'Image URL')
            ->setNullable()
            ->addRule(Form::URL);

        $form->addText('from', 'Sender');

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
        $this->articleLocker->setupLockLink('Pridajte sa k predplatiteľom', 'https://predplatne.dennikn.sk/ecko');

        $errors = [];

        $post = $values['grafdna_html'];
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
            "/\[embed\](.*?)\[\/embed\]/is" => function ($matches) {
                return '<p>Graf nájdete aj na <a href="' . $matches[1] . '" style="padding:0;margin:0;line-height:1.3;color:' . $this->linksColor . ';text-decoration:underline;">' . $matches[1] . ' </a>.</p>';
            },
            "/^(http|https)\:\/\/[a-zA-Z0-9\-\.]*(flourish|datawrapper)+[a-zA-Z0-9\-\.]*\.[a-zA-Z]+(\/\S*)?\s*$/im" => function ($matches) {
                return '<p>Graf nájdete aj na <a href="' . $matches[0] . '" style="padding:0;margin:0;line-height:1.3;color:' . $this->linksColor . ';text-decoration:underline;">' . $matches[0] . ' </a>.</p>';
            },
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
            'html' => $lockedPost,
            'text' => strip_tags($lockedPost),
            'excerpt' => $excerpt,
            'economyPosts' => $economyPosts,
            'adSnippetHtml' => $adSnippet?->html,
            'adSnippetText' => $adSnippet?->text,
        ];

        $sourceTemplate = $this->mailSourceTemplateRepository->find($values['source_template_id']);
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

        if (!isset($data->post_title)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_title'");
        }
        $output->title = $data->post_title;

        if (!isset($data->post_url)) {
            throw new PreprocessException("WP json object  does not contain required attribute 'post_url'");
        }
        $output->url = $data->post_url;

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
