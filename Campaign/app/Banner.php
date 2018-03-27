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
        'transition',
        'closeable',
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

    public function fillTemplate(array $attributes)
    {
        $relationName = $this->getTemplateRelationName();

        if ($this[$relationName]) {
            $this->{$relationName}->fill($attributes);
        } else {
            $this->setRelation($relationName, $this->{$relationName}()->make($attributes));
        }

        return $this;
    }

    public function withTemplate()
    {
        return $this->with($this->getTemplateRelationName());
    }

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
}
