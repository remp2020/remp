<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Variable extends Model
{
    const REDIS_CACHE_KEY = 'variables';

    protected $fillable = [
        'name',
        'value',
    ];

    public static function refreshVariableCache()
    {
        $variables = Variable::all()->pluck('value', 'name');
        Redis::set(self::REDIS_CACHE_KEY, json_encode($variables));
    }
}
