<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Property
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Property whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Property whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Property whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Property whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Property whereUuid($value)
 * @mixin \Eloquent
 * @property string $name
 * @method static \Illuminate\Database\Query\Builder|\App\Property whereName($value)
 * @property-read \App\Account $account
 */
class Property extends Model
{
    protected $fillable = ['uuid', 'name'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
