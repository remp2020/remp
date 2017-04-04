<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Account
 *
 * @property string $uuid
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Account whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Account whereUuid($value)
 * @mixin \Eloquent
 * @property int $id
 * @method static \Illuminate\Database\Query\Builder|\App\Account whereId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Property[] $properties
 */
class Account extends Model
{
    protected $fillable = ['uuid', 'name'];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
