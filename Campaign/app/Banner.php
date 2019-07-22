<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;

class Banner extends Model
{
    use Notifiable;

    const TEMPLATE_HTML = 'html';
    const TEMPLATE_MEDIUM_RECTANGLE = 'medium_rectangle';
    const TEMPLATE_OVERLAY_RECTANGLE = 'overlay_rectangle';
    const TEMPLATE_HTML_OVERLAY = 'html_overlay';
    const TEMPLATE_BAR = 'bar';
    const TEMPLATE_COLLAPSIBLE_BAR = 'collapsible_bar';
    const TEMPLATE_SHORT_MESSAGE = 'short_message';

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
    ];

    protected $casts = [
        'closeable' => 'boolean',
        'js_includes' => 'array',
        'css_includes' => 'array',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Banner $banner) {
            $banner->uuid = Uuid::uuid4()->toString();
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

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_banners')->whereNull('deleted_at');
    }

    public function htmlTemplate()
    {
        return $this->hasOne(HtmlTemplate::class);
    }

    public function mediumRectangleTemplate()
    {
        return $this->hasOne(MediumRectangleTemplate::class);
    }

    public function overlayRectangleTemplate()
    {
        return $this->hasOne(OverlayRectangleTemplate::class);
    }

    public function htmlOverlayTemplate()
    {
        return $this->hasOne(HtmlOverlayTemplate::class);
    }

    public function barTemplate()
    {
        return $this->hasOne(BarTemplate::class);
    }

    public function collapsibleBarTemplate()
    {
        return $this->hasOne(CollapsibleBarTemplate::class);
    }

    public function shortMessageTemplate()
    {
        return $this->hasOne(ShortMessageTemplate::class);
    }

    /**
     * Returns name of the banner to template relation
     *
     * @return string $relationName
     * @throws \Exception
     */
    public function getTemplateRelationName()
    {
        switch ($this->template) {
            case self::TEMPLATE_HTML:
                return 'htmlTemplate';
            case self::TEMPLATE_MEDIUM_RECTANGLE:
                return 'mediumRectangleTemplate';
            case self::TEMPLATE_BAR:
                return 'barTemplate';
                break;
            case self::TEMPLATE_COLLAPSIBLE_BAR:
                return 'collapsibleBarTemplate';
                break;
            case self::TEMPLATE_SHORT_MESSAGE:
                return 'shortMessageTemplate';
            case self::TEMPLATE_OVERLAY_RECTANGLE:
                return 'overlayRectangleTemplate';
            case self::TEMPLATE_HTML_OVERLAY:
                return 'htmlOverlayTemplate';
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @throws \Exception
     */
    public function getTemplateRelation($relationName = null)
    {
        if ($relationName === null) {
            $relationName = $this->getTemplateRelationName();
        }
        return $this->$relationName();
    }

    /**
     * Returns banner template object
     *
     * @return Model
     * @throws \Exception
     */
    public function getTemplate()
    {
        return $this->getRelationValue($this->getTemplateRelationName());
    }
}
