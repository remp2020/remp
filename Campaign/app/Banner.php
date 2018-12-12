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
    const TEMPLATE_BAR = 'bar';
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
    ];

    protected $casts = [
        'closeable' => 'boolean',
    ];

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

    public function barTemplate()
    {
        return $this->hasOne(BarTemplate::class);
    }

    public function shortMessageTemplate()
    {
        return $this->hasOne(ShortMessageTemplate::class);
    }

    /**
     * Returns name of the banner to template relation
     *
     * @return string $relationName
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
            case self::TEMPLATE_SHORT_MESSAGE:
                return 'shortMessageTemplate';
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
     */
    public function getTemplate()
    {
        return $this->getRelationValue($this->getTemplateRelationName());
    }
}
