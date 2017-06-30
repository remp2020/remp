<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

/**
 * App\Campaign
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property bool $active
 * @property int $banner_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Banner $banner
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CampaignSegment[] $segments
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereBannerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereSegmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Campaign whereUuid($value)
 * @mixin \Eloquent
 */
class Campaign extends Model
{
    protected $fillable = [
        'name',
        'banner_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function(Campaign $campaign) {
            $campaign->uuid = Uuid::uuid4()->toString();
        });
    }

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    public function segments()
    {
        return $this->hasMany(CampaignSegment::class);
    }
}
