<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Snippet extends Model
{
    const REDIS_CACHE_KEY = 'snippets';

    protected $fillable = [
        'name',
        'value',
    ];

    public static function refreshSnippetsCache()
    {
        $snippets = Snippet::all()->pluck('value', 'name');
        Redis::set(self::REDIS_CACHE_KEY, json_encode($snippets));
    }
}
