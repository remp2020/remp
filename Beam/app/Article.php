<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class Article extends Model
{
    protected $fillable = [
        'property_uuid',
        'external_id',
        'title',
        'author',
        'url',
        'section',
        'image_url',
        'published_at',
        'pageviews_all',
        'pageviews_signed_in',
        'pageviews_subscribers',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_uuid', 'uuid');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }

    public function pageviews()
    {
        return $this->hasMany(ArticlePageviews::class);
    }

    public function timespent()
    {
        return $this->hasMany(ArticleTimespent::class);
    }

    public function setPublishedAtAttribute($value)
    {
        if (!$value) {
            return;
        }
        $this->attributes['published_at'] = new Carbon($value);
    }

    /**
     * Check if Illuminate\Database\QueryException is Duplicate Entry Exception.
     */
    protected function isDuplicateEntryException(QueryException $e): bool
    {
        $errorCode  = $e->errorInfo[1];
        if ($errorCode === 1062) { // Duplicate Entry error code
            return true;
        }
        return false;
    }

    /**
     * Get the first record matching the attributes or create it.
     */
    public static function firstOrCreate(array $attributes, array $values = []): Article
    {
        try {
            $static = (new static);
            return $static->create($attributes + $values);
        } catch (QueryException $e){
            if ($static->isDuplicateEntryException($e)) {
                return $static->where($attributes)->first();
            }
            throw $e;
        }
    }
}
