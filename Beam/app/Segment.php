<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Segment
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Segment whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Segment whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Segment whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Segment whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Segment whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Segment whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SegmentRule[] $rules
 */
class Segment extends Model
{
    protected $casts = [
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => false,
    ];

    protected $fillable = [
        'name',
        'code',
        'active',
    ];

    public function rules()
    {
        return $this->hasMany(SegmentRule::class);
    }
}
