<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
 */
class Campaign extends Model
{
    public function banner()
    {
        return $this->hasOne(Banner::class);
    }
}
