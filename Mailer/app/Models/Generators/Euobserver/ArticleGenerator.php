<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators\Euobserver;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\EuobserverArticleWidget\EuobserverArticleWidget;
use Remp\Mailer\Models\Generators\EmbedParser;
use Remp\Mailer\Models\Generators\RulesTrait;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Models\Generators\PreprocessException;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class ArticleGenerator implements IGenerator
{
    use RulesTrait;

    public $onSubmit;

    private bool $lockingEnabled = false;

    protected function getLinksColor(): string
    {
        return '#f0523c';
    }

    private const LOCK_BLOCK = '<!-- wp:eo/lock -->';
    private const ARTICLE_BASE_URL = 'https://euobserver.com/';

    public function __construct(
        protected readonly SourceTemplatesRepository $mailSourceTemplateRepository,
        protected readonly ContentInterface $content,
        protected readonly EmbedParser $embedParser,
        protected readonly EngineFactory $engineFactory,
    ) {
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('article_html'))->setRequired(),
            (new PostInputParam('url'))->setRequired(),
            (new PostInputParam('title'))->setRequired(),
            (new PostInputParam('editor'))->setRequired(),
            (new PostInputParam('from'))->setRequired(),
            (new PostInputParam('summary')),
            (new PostInputParam('editor_avatar_url')),
            (new PostInputParam('featured_image_url')),
            (new PostInputParam('featured_image_title')),
        ];
    }

    public function getWidgets(): array
    {
        return [EuobserverArticleWidget::class];
    }

    public function process(array $values): array
    {
        $sourceTemplate = $this->mailSourceTemplateRepository->find($values['source_template_id']);
        $errors = [];
        $articleLinkPlaceholders = [];

        $post = $values['article_html'];

        // Replace eo/link blocks with unique placeholders, fetching metadata now.
        // This must happen before comment stripping so we can read the block attributes,
        // and before rules run so the rendered card HTML is not re-processed.
        $post = $this->processArticleLinks($post, $articleLinkPlaceholders, $errors);

        // The locked version is everything before the eo/lock block.
        $lockedPost = $this->splitOnLock($post);

        $post = $this->stripBlockComments($post);
        $lockedPost = $this->stripBlockComments($lockedPost);

        $post = $this->preprocessBlockHtml($post);
        $lockedPost = $this->preprocessBlockHtml($lockedPost);

        $rules = $this->getRules();
        foreach ($rules as $rule => $replace) {
            if (is_array($replace) || is_callable($replace)) {
                $post = preg_replace_callback($rule, $replace, $post);
                $lockedPost = preg_replace_callback($rule, $replace, $lockedPost);
            } else {
                $post = preg_replace($rule, $replace, $post);
                $lockedPost = preg_replace($rule, $replace, $lockedPost);
            }
        }

        // Substitute article card placeholders after rules to prevent re-processing of card HTML.
        $post = str_replace(array_keys($articleLinkPlaceholders), array_values($articleLinkPlaceholders), $post);
        $lockedPost = str_replace(array_keys($articleLinkPlaceholders), array_values($articleLinkPlaceholders), $lockedPost);

        $lockedPost = $this->injectLockedCta($lockedPost);

        $params = $this->buildParams($values, $post, false);
        $lockedParams = $this->buildParams($values, $lockedPost, true);

        $engine = $this->engineFactory->engine();

        $params['html'] = $engine->markSafe($params['html']);
        $params['text'] = $engine->markSafe($params['text']);

        $result = [
            'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
            'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
            'errors' => $errors,
        ];

        if ($this->lockingEnabled) {
            $lockedParams['html'] = $engine->markSafe($lockedParams['html']);
            $lockedParams['text'] = $engine->markSafe($lockedParams['text']);

            $result['lockedHtmlContent'] = $engine->render($sourceTemplate->content_html, $lockedParams);
            $result['lockedTextContent'] = strip_tags($engine->render($sourceTemplate->content_text, $lockedParams));
        }

        return $result;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $output = $this->process((array) $values);

        $addonParams = [
            'lockingEnabled' => $this->lockingEnabled,
            'articleTitle' => $values->title,
            'from' => $values->from,
            'render' => true,
            'articleId' => $values->article_id ?? null,
            'errors' => $output['errors'],
        ];

        if ($this->lockingEnabled) {
            $addonParams['lockedHtmlContent'] = $output['lockedHtmlContent'];
            $addonParams['lockedTextContent'] = $output['lockedTextContent'];
        }

        $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
    }

    public function generateForm(Form $form): void
    {
        $form->offsetUnset(Form::ProtectorId);

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addText('url', 'Article URL')
            ->addRule(Form::URL)
            ->setRequired("Field 'Article URL' is required.");

        $form->addText('from', 'Sender');

        $form->addText('featured_image_url', 'Featured image URL');
        $form->addText('featured_image_title', 'Featured image title');

        $form->addText('editor', 'Editor')
            ->setRequired("Field 'Editor' is required.");

        $form->addText('editor_avatar_url', 'Editor avatar URL');

        $form->addTextArea('summary', 'Summary')
            ->setHtmlAttribute('rows', 3)
            ->setRequired(false);

        $form->addTextArea('article_html', 'HTML')
            ->setHtmlAttribute('rows', 20)
            ->setHtmlAttribute('class', 'form-control html-editor')
            ->getControlPrototype();

        $form->addHidden('article_id');

        $form->addSubmit('send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-magic"></i> Generate');

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        $output = new ArrayHash();

        if (!isset($data->post_authors[0]->display_name)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_authors.0.display_name'");
        }

        $output->editor = $data->post_authors[0]->display_name;
        $output->editor_avatar_url = $data->post_authors[0]->avatar_url ?? null;

        if (isset($data->sender_email) && $data->sender_email) {
            $output->from = $data->sender_email;
        } else {
            $output->from = 'EUobserver <info@euobserver.com>';
            foreach ($data->post_authors as $author) {
                if ($author->user_email ?? null) {
                    $output->from = $author->display_name . ' <' . $author->user_email . '>';
                    break;
                }
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

        $output->summary = $data->post_excerpt ?? null;

        if (!isset($data->post_content)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_content'");
        }
        $output->article_html = $data->post_content;

        $output->article_id = $data->ID ?? null;
        $output->featured_image_url = $data->post_image->image_sizes->medium->file
            ?? $data->post_image->image_sizes->full->file
            ?? null;
        $output->featured_image_title = $data->post_image->image_title ?? null;

        return $output;
    }

    // -------------------------------------------------------------------------
    // Preprocessing helpers
    // -------------------------------------------------------------------------

    /**
     * processArticleLinks extracts the article ID from WP block comment and fetches the metadata.
     *
     * Example: <!-- wp:eo/link {"id":210923} /-->
     */
    private function processArticleLinks(string $post, array &$placeholders, array &$errors): string
    {
        $counter = 0;
        return preg_replace_callback(
            '/<!--\s*wp:eo\/link\s+({[^}]+})\s*\/-->/i',
            function (array $matches) use (&$errors, &$counter, &$placeholders): string {
                try {
                    $data = Json::decode($matches[1]);
                    $id = $data->id ?? null;
                    if (!$id) {
                        return '';
                    }

                    $url = self::ARTICLE_BASE_URL . $id . '/';
                    $meta = $this->content->fetchUrlMeta($url);
                    if (!$meta) {
                        $errors[(string) $id] = "Could not fetch metadata for linked article {$id}";
                        return '';
                    }
                    $cardHtml = ($this->getArticleLinkTemplateFunction())($meta->getTitle(), $url, $meta->getImage());
                    $key = "<!--EO_ARTICLE_LINK_{$counter}-->";
                    $placeholders[$key] = $cardHtml;
                    $counter++;
                    return $key;
                } catch (InvalidUrlException | JsonException $e) {
                    $errors[$matches[0]] = 'Could not fetch linked article: ' . $e->getMessage();
                    return '';
                }
            },
            $post
        );
    }

    private function splitOnLock(string $post): string
    {
        $lockPos = stripos($post, self::LOCK_BLOCK);
        if ($lockPos === false) {
            return $post;
        }
        return substr($post, 0, $lockPos);
    }

    private function stripBlockComments(string $post): string
    {
        return preg_replace('/<!--\s*\/?wp:[^>]*-->/s', '', $post);
    }

    /**
     * preprocessBlockHtml converts special HTML comments generated by block editor to actual HTML so that the RulesTrait
     * can work with them later as usually.
     */
    private function preprocessBlockHtml(string $post): string
    {
        // Convert wp-block-embed figures to bare URLs so the embed rule in getRules() picks them up.
        $post = preg_replace_callback(
            '/<figure\b[^>]*class="[^"]*wp-block-embed[^"]*"[^>]*>.*?<div\b[^>]*class="[^"]*wp-block-embed__wrapper[^"]*"[^>]*>\s*(https?:\/\/\S+)\s*<\/div>.*?<\/figure>/is',
            fn(array $m): string => "\n" . trim($m[1]) . "\n",
            $post
        );

        // Convert wp-block-image figures to either a [caption] shortcode (handled by getRules()) or
        // a bare <img> tag (handled by the image rule in getRules()).
        $post = preg_replace_callback(
            '/<figure\b[^>]*class="[^"]*wp-block-image[^"]*"[^>]*>\s*(<img[^>]*(?:\/>|>))\s*(?:<figcaption[^>]*>(.*?)<\/figcaption>)?\s*<\/figure>/is',
            function (array $m): string {
                $caption = isset($m[2]) ? trim(strip_tags($m[2])) : null;
                preg_match('/src="([^"]+)"/', $m[1], $src);
                $imgSrc = $src[1] ?? '';

                if ($caption) {
                    $caption = htmlspecialchars($this->decodeEntities($caption), ENT_QUOTES);
                    // [caption] format is matched by the caption rule in RulesTrait::getRules().
                    return '[caption]<img src="' . $imgSrc . '" alt=""/>' . $caption . '[/caption]';
                }
                return '<img src="' . $imgSrc . '" alt="" />';
            },
            $post
        );

        // Strip empty paragraphs used as spacers in the block editor.
        $post = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $post);

        return $post;
    }

    private function injectLockedCta(string $post): string
    {
        // The actual CTA markup is expected to be defined as a Twig snippet named 'eo-subscribe-cta'
        // in the source template.
        return $post . "\n\n{{ include('eo-subscribe-cta') }}\n";
    }

    public function getArticleLinkTemplateFunction(): callable
    {
        return static function ($title, $url, $image) {
            return <<<HTML
                        <table style="border-spacing:0;border-collapse:collapse;vertical-align:top;text-align:left;font-family:Helvetica,Arial,sans-serif;width:100%;color:#181818;padding:0;margin:0;line-height:1.3;background:#ffffff;border:1px solid #e0e0e0;">
                            <tr style="padding:0;vertical-align:top;text-align:left;">
                                <td valign="top" style="vertical-align:top;text-align:left;border-collapse:collapse !important;padding:12px;">
                                    <a href="{$url}" style="text-decoration:none;color:#181818;display:block;width:100%;max-width:100%;height:auto;border:none;">
                                        <img src="{$image}" style="display:block;width:100%;max-width:100%;height:auto;border:none;margin-bottom:12px;" alt="{$title}">
                                    </a>
                                    <small style="margin:0;padding:0;margin-bottom:6px;display:block;font-size:14px;color:#808090;font-family:Helvetica,Arial,sans-serif;">Read more</small>
                                    <h2 style="margin:0;padding:0;font-size:22px;font-family:Georgia,'Times New Roman',serif;font-weight:700;line-height:1.0;">
                                        <a href="{$url}" style="text-decoration:underline;color:#f0523c;">{$title}</a>
                                    </h2>
                                </td>
                            </tr>
                        </table>
                HTML;
        };
    }

    private function decodeEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function buildParams(array $values, string $html, bool $locked): array
    {
        return [
            'title' => $values['title'],
            'editor' => $values['editor'],
            'editor_avatar_url' => $values['editor_avatar_url'] ?? null,
            'featured_image_url' => $values['featured_image_url'] ?? null,
            'featured_image_title' => isset($values['featured_image_title'])
                ? $this->decodeEntities($values['featured_image_title'])
                : null,
            'summary' => $values['summary'] ?? null,
            'url' => $values['url'],
            'html' => $html,
            'text' => strip_tags($html),
            'locked' => $locked,
        ];
    }

    // -------------------------------------------------------------------------
    // Template definitions (used by RulesTrait::getRules())
    // -------------------------------------------------------------------------

    public function getTemplates(): array
    {
        $captionTemplate = <<< HTML
    <img src="$1" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:5px;">
    <p style="color:#181818;padding:0;margin:0;Margin:0;font-size:18px;line-height:160%;margin-bottom:26px;Margin-bottom:26px;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">
        <small class="text-gray" style="font-size:13px;line-height:18px;display:block;color:#9B9B9B;">$2</small>
    </p>
HTML;

        $captionWithLinkTemplate = <<< HTML
    <a href="$1" style="color:#181818;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-weight:normal;padding:0;margin:0;Margin:0;text-align:left;line-height:1.3;color:{$this->getLinksColor()};text-decoration:none;">
    <img src="$2" alt="" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:block;margin-bottom:5px;border:none;">
</a>
    <p style="color:#181818;padding:0;margin:0;Margin:0;font-size:18px;line-height:160%;margin-bottom:26px;Margin-bottom:26px;text-align:left;font-weight:normal;word-wrap:break-word;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;border-collapse:collapse !important;">
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
        <img src="$1" alt="" width="564" style="outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width: 100%;max-width:100%;clear:both;display:block;margin-bottom:20px;">
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
