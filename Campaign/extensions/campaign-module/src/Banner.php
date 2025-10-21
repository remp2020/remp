<?php

namespace Remp\CampaignModule;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Predis\ClientInterface;
use Ramsey\Uuid\Uuid;
use Remp\CampaignModule\Concerns\HasCacheableRelation;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Banner extends Model implements Searchable
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;
    use HasCacheableRelation;

    use Notifiable;
    use IdentificationTrait;

    private const BANNER_TAG = 'banner';
    private const BANNER_JSON_TAG = 'banner_json';

    public const POSITION_TOP_LEFT = 'top_left';
    public const POSITION_TOP_RIGHT = 'top_right';
    public const POSITION_BOTTOM_LEFT = 'bottom_left';
    public const POSITION_BOTTOM_RIGHT = 'bottom_right';

    public const DISPLAY_TYPE_INLINE = 'inline';
    public const DISPLAY_TYPE_OVERLAY = 'overlay';

    public const TEMPLATE_HTML = 'html';
    public const TEMPLATE_MEDIUM_RECTANGLE = 'medium_rectangle';
    public const TEMPLATE_OVERLAY_RECTANGLE = 'overlay_rectangle';
    public const TEMPLATE_HTML_OVERLAY = 'html_overlay';
    public const TEMPLATE_BAR = 'bar';
    public const TEMPLATE_COLLAPSIBLE_BAR = 'collapsible_bar';
    public const TEMPLATE_SHORT_MESSAGE = 'short_message';
    public const TEMPLATE_OVERLAY_TWO_BUTTONS_SIGNATURE = 'overlay_two_buttons_signature';
    public const TEMPLATE_NEWSLETTER_RECTANGLE = 'newsletter_rectangle';

    protected $fillable = [
        'name',
        'target_url',
        'position',
        'offset_vertical',
        'offset_horizontal',
        'transition',
        'closeable',
        'close_text',
        'display_delay',
        'close_timeout',
        'display_type',
        'target_selector',
        'template',
        'js',
        'js_includes',
        'css_includes',
        'manual_events_tracking',
    ];

    protected $casts = [
        'closeable' => 'boolean',
        'manual_events_tracking' => 'boolean',
        'js_includes' => 'array',
        'css_includes' => 'array',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';
    
    protected $cacheableRelations = [
        'htmlTemplate' => HtmlTemplate::class,
        'mediumRectangleTemplate' => MediumRectangleTemplate::class,
        'barTemplate' => BarTemplate::class,
        'collapsibleBarTemplate' => CollapsibleBarTemplate::class,
        'shortMessageTemplate' => ShortMessageTemplate::class,
        'overlayRectangleTemplate' => OverlayRectangleTemplate::class,
        'htmlOverlayTemplate' => HtmlOverlayTemplate::class,
        'overlayTwoButtonsSignatureTemplate' => OverlayTwoButtonsSignatureTemplate::class,
        'newsletterRectangleTemplate' => NewsletterRectangleTemplate::class,
    ];

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Banner $banner) {
            $banner->uuid = self::generateUuid();
            $banner->public_id = self::generatePublicId();
        });
    }

    public function fill(array $attributes)
    {
        parent::fill($attributes);

        return $this->template ? $this->fillTemplate($attributes) : $this;
    }

    /**
     * Fill banner's template relation
     *
     * If template relation doesn't exists it's using:
     * string $this->getTemplateRelationName()
     * to initialize relation.
     *
     * @param array $attributes
     * @return $this
     * @throws \Exception
     */
    public function fillTemplate(array $attributes)
    {
        $relationName = $this->getTemplateRelationName();

        // fill existing related model
        if (!is_null($this->id)) {
            $this->getRelationValue($relationName)->fill($attributes);

        // create and fill relation
        } else {
            $this->setRelation(
                $relationName,
                $this->getTemplateRelation($relationName)->make($attributes)
            );
        }

        return $this;
    }

    /**
     * load template
     *
     * @return $this
     * @throws \Exception
     */
    public function loadTemplate()
    {
        return $this->load($this->getTemplateRelationName());
    }

    /**
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_banners')->whereNull('deleted_at');
    }

    /**
     * @return HasOne<HtmlTemplate, $this>
     */
    public function htmlTemplate(): HasOne
    {
        return $this->hasOne(HtmlTemplate::class);
    }

    /**
     * @return HasOne<MediumRectangleTemplate, $this>
     */
    public function mediumRectangleTemplate(): HasOne
    {
        return $this->hasOne(MediumRectangleTemplate::class);
    }

    /**
     * @return HasOne<OverlayRectangleTemplate, $this>
     */
    public function overlayRectangleTemplate(): HasOne
    {
        return $this->hasOne(OverlayRectangleTemplate::class);
    }

    /**
     * @return HasOne<HtmlOverlayTemplate, $this>
     */
    public function htmlOverlayTemplate(): HasOne
    {
        return $this->hasOne(HtmlOverlayTemplate::class);
    }

    /**
     * @return HasOne<OverlayTwoButtonsSignatureTemplate, $this>
     */
    public function overlayTwoButtonsSignatureTemplate(): HasOne
    {
        return $this->hasOne(OverlayTwoButtonsSignatureTemplate::class);
    }

    /**
     * @return HasOne<BarTemplate, $this>
     */
    public function barTemplate(): HasOne
    {
        return $this->hasOne(BarTemplate::class);
    }

    /**
     * @return HasOne<CollapsibleBarTemplate, $this>
     */
    public function collapsibleBarTemplate(): HasOne
    {
        return $this->hasOne(CollapsibleBarTemplate::class);
    }

    /**
     * @return HasOne<ShortMessageTemplate, $this>
     */
    public function shortMessageTemplate(): HasOne
    {
        return $this->hasOne(ShortMessageTemplate::class);
    }

    /**
     * @return HasOne<NewsletterRectangleTemplate, $this>
     */
    public function newsletterRectangleTemplate(): HasOne
    {
        return $this->hasOne(NewsletterRectangleTemplate::class);
    }

    public function getTemplateRelationName()
    {
        switch ($this->template) {
            case self::TEMPLATE_HTML:
                return 'htmlTemplate';
            case self::TEMPLATE_MEDIUM_RECTANGLE:
                return 'mediumRectangleTemplate';
            case self::TEMPLATE_BAR:
                return 'barTemplate';
            case self::TEMPLATE_COLLAPSIBLE_BAR:
                return 'collapsibleBarTemplate';
            case self::TEMPLATE_SHORT_MESSAGE:
                return 'shortMessageTemplate';
            case self::TEMPLATE_OVERLAY_RECTANGLE:
                return 'overlayRectangleTemplate';
            case self::TEMPLATE_HTML_OVERLAY:
                return 'htmlOverlayTemplate';
            case self::TEMPLATE_OVERLAY_TWO_BUTTONS_SIGNATURE:
                return 'overlayTwoButtonsSignatureTemplate';
            case self::TEMPLATE_NEWSLETTER_RECTANGLE:
                return 'newsletterRectangleTemplate';
            default:
                throw new \Exception('Unhandled banner template access: ' . $this->template);
        }
    }

    /**
     * Returns banner template relation
     *
     * Using $this->getTemplateRelationName()
     * to return template relation.
     *
     * @param string $relationName
     * @return HasOne
     * @throws \Exception
     */
    public function getTemplateRelation($relationName = null): HasOne
    {
        if ($relationName === null) {
            $relationName = $this->getTemplateRelationName();
        }
        return $this->$relationName();
    }

    public function getTemplate(): AbstractTemplate
    {
        return $this->getRelationValue($this->getTemplateRelationName());
    }

    public function cache()
    {
        $bannerTemplate = $this->loadTemplate();
        Redis::set(self::BANNER_JSON_TAG . ":{$this->id}", $bannerTemplate->toJson());
    }

    public static function loadCachedBanner(ClientInterface $redis, string $bannerId): ?Banner
    {
        $serializedBanner = $redis->get(Banner::BANNER_TAG . ":$bannerId");
        if ($serializedBanner) {
            return unserialize($serializedBanner, [__CLASS__]);
        }
        return null;
    }

    public function getUsedSnippetCodes(): array
    {
        $allSnippets = [];
        $processedSnippets = [];

        $snippetsToProcess = [];

        // Extract snippets from banner's JS
        if ($this->js) {
            $snippetsToProcess = array_merge($snippetsToProcess, $this->extractSnippetCodes($this->js));
        }

        $jsIncludes = array_filter($this->js_includes ?? []);
        foreach ($jsIncludes as $jsInclude) {
            $snippetsToProcess = array_merge($snippetsToProcess, $this->extractSnippetCodes($jsInclude));
        }
        $cssIncludes = array_filter($this->css_includes ?? []);
        foreach ($cssIncludes as $cssInclude) {
            $snippetsToProcess = array_merge($snippetsToProcess, $this->extractSnippetCodes($cssInclude));
        }

        // Extract snippets from template text and CSS fields
        $snippetsToProcess = array_merge($snippetsToProcess, $this->extractSnippetsFromTemplate());

        // Check if snippets use another snippets
        while (!empty($snippetsToProcess)) {
            $currentSnippet = array_shift($snippetsToProcess);
            if (in_array($currentSnippet, $processedSnippets, true)) {
                continue;
            }

            $processedSnippets[] = $currentSnippet;
            $allSnippets[] = $currentSnippet;

            $snippet = Snippet::where('name', $currentSnippet)->first();

            if ($snippet && $snippet->value) {
                // Extract nested snippets from this snippet's value
                $nestedSnippets = $this->extractSnippetCodes($snippet->value);

                // Add new nested snippets to processing queue
                foreach ($nestedSnippets as $nestedSnippet) {
                    if (!in_array($nestedSnippet, $processedSnippets, true)) {
                        $snippetsToProcess[] = $nestedSnippet;
                    }
                }
            }
        }

        return $allSnippets;
    }

    private function extractSnippetCodes(string $content): array
    {
        $matches = [];
        $matched = preg_match_all('/{{\s?(.*?)\s?}}/', $content, $matches, PREG_PATTERN_ORDER);

        if (is_int($matched) && $matched > 0) {
            return $matches[1];
        }

        return [];
    }

    private function extractSnippetsFromTemplate(): array
    {
        $snippets = [];
        $template = $this->getTemplate();

        foreach ($template->getSnippetFields() as $field) {
            if (isset($template->$field)) {
                $snippets = array_merge($snippets, $this->extractSnippetCodes($template->$field));
            }
        }

        return $snippets;
    }
}
