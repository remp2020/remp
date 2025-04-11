<?php
namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Database\Factories\ArticleViewsSnapshotFactory;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\BeamModule\Model\Scopes\PropertyTokenScope;
use DB;

class ArticleViewsSnapshot extends BaseModel
{
    use HasFactory;

    use TableName;

    public $timestamps = false;

    protected $fillable = [
        'time',
        'property_token',
        'external_article_id',
        'referer_medium',
        'count'
    ];

    protected $casts = [
        'count' => 'integer',
        'time' => 'datetime',
    ];

    protected static function newFactory(): ArticleViewsSnapshotFactory
    {
        return ArticleViewsSnapshotFactory::new();
    }

    /**
     * By default, apply property token filtering when selecting snapshot data
     */
    protected static function boot()
    {
        parent::boot();
        $selectedProperty = resolve(SelectedProperty::class);
        static::addGlobalScope(new PropertyTokenScope($selectedProperty));
    }

    public static function deleteForTimes(array $times): int
    {
        return DB::table(self::getTableName())
            ->whereIn('time', $times)
            ->delete();
    }
}
