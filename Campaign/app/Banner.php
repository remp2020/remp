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

    protected $with = [
        'htmlTemplate',
        'mediumRectangleTemplate',
        'barTemplate',
        'shortMessageTemplate',
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
        switch ($this->template) {
            case self::TEMPLATE_HTML:
                $this->htmlTemplate ?
                    $this->htmlTemplate->fill($attributes) :
                    $this->setRelation('htmlTemplate', $this->htmlTemplate()->make($attributes));
                break;
            case self::TEMPLATE_MEDIUM_RECTANGLE:
                $this->mediumRectangleTemplate ?
                    $this->mediumRectangleTemplate->fill($attributes) :
                    $this->setRelation('mediumRectangleTemplate', $this->mediumRectangleTemplate()->make($attributes));
                break;
            case self::TEMPLATE_BAR:
                $this->barTemplate ?
                    $this->barTemplate->fill($attributes) :
                    $this->setRelation('barTemplate', $this->barTemplate()->make($attributes));
                break;
            case self::TEMPLATE_SHORT_MESSAGE:
                $this->shortMessageTemplate ?
                    $this->shortMessageTemplate->fill($attributes) :
                    $this->setRelation('shortMessageTemplate', $this->shortMessageTemplate()->make($attributes));
                break;
        }
        return $this;
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class)->orWhere([
            'alt_banner_id' => $this->id,
        ]);
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
}
