<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Snippet extends Model implements Searchable
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

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
