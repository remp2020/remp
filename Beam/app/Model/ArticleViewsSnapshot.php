<?php
namespace App\Model;

use DB;
use Illuminate\Database\Eloquent\Model;

class ArticleViewsSnapshot extends Model
{
    use TableName;

    public $timestamps = false;

    protected $fillable = [
        'time',
        'property_token',
        'external_article_id',
        'derived_referer_medium',
        'explicit_referer_medium',
        'count',
        'count_by_referer'
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    protected $dates = [
        'time',
    ];

    public static function deleteForTimes(array $times): int
    {
        return DB::table(self::getTableName())
            ->whereIn('time', $times)
            ->delete();
    }
}
