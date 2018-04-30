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
        $relation = $this->getTemplateRelation();

        // fill existing related model
        if ($relation && !is_null($this->id)) {
            $relation->get()->first()->fill($attributes);

        // create and fill relation
        } else {
            $this->setRelation(
                $relationName,
                optional(
                    $this->{$relationName}()
                )->make($attributes)
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
        return $this->belongsToMany(Campaign::class, 'campaign_banners');
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
        $relationName = null;

        switch ($this->template) {
            case self::TEMPLATE_HTML:
                $relationName = 'htmlTemplate';
                break;
            case self::TEMPLATE_MEDIUM_RECTANGLE:
                $relationName = 'mediumRectangleTemplate';
                break;
            case self::TEMPLATE_BAR:
                $relationName = 'barTemplate';
                break;
            case self::TEMPLATE_SHORT_MESSAGE:
                $relationName = 'shortMessageTemplate';
                break;
            default:
                throw new \Exception('Not existing template name: ' . $this->template);
                break;
        }

        return $relationName;
    }

    /**
     * Returns banner template relation
     *
     * Using $this->getTemplateRelationName()
     * to return template relation.
     *
     * @return void
     */
    public function getTemplateRelation()
    {
        $relationName = $this->getTemplateRelationName();

        return $this->$relationName();
    }

    /**
     * Returns banner template object
     *
     * @return void
     */
    public function getTemplate()
    {
        $this->getTemplateRelation()
            ->first()
            ->get();
    }
}
