<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Banner
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property int $height
 * @property int $width
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $target_url
 * @property string $text_color
 * @property string $background_color
 * @property string $position
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereBackgroundColor($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereHeight($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner wherePosition($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereTargetUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereTextColor($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereUuid($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereWidth($value)
 * @mixin \Eloquent
 */
class Banner extends Model
{
    protected $fillable = ['name', 'width', 'height', 'target_url', 'text_color', 'background_color', 'position'];
}
