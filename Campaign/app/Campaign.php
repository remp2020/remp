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
 * @property string $segment_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereBannerId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereSegmentId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Campaign whereUuid($value)
 * @mixin \Eloquent
 * @property-read \App\Banner $banner
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CampaignRule[] $rules
 */
class Campaign extends Model
{
    protected $fillable = [
        'name',
        'banner_id',
        'segment_id',
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

    public function rules()
    {
        return $this->hasMany(CampaignRule::class);
    }
}
