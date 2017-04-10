<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Banner
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $storage_uri
 * @property int $height
 * @property int $width
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereHeight($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereStorageUri($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereUuid($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Banner whereWidth($value)
 * @mixin \Eloquent
 */
class Banner extends Model
{
    protected $fillable = ['name', 'storage_uri', 'height', 'width'];
}
