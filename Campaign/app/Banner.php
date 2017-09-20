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
    ];

    protected $casts = [
        'closeable' => 'boolean',
    ];

    protected $attributes = [
        'closeable' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Banner $banner) {
            $banner->uuid = Uuid::uuid4()->toString();
        });
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function htmlTemplate()
    {
        return $this->hasOne(HtmlTemplate::class);
    }

    public function mediumRectangleTemplate()
    {
        return $this->hasOne(MediumRectangleTemplate::class);
    }
}
