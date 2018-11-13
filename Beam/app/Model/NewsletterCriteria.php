<?php
namespace App\Model;

use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Conversion;
use Carbon\Carbon;
use Cache;
use Illuminate\Support\Collection;
use MabeEnum\Enum;
use Recurr\Exception;

class NewsletterCriteria extends Enum
{
    const AVERAGE_PAYMENT = 'average_payment';
    const TIMESPENT_ALL = 'timespent_all';
    const PAGEVIEWS_SIGNED_IN = 'pageviews_signed_in';
    const PAGEVIEWS_SUBSCRIBERS = 'pageviews_subscribers';
    const TIMESPENT_SUBSCRIBERS = 'timespent_subscribers';
    const CONVERSIONS = 'conversions';
    const TIMESPENT_SIGNED_IN = 'timespent_signed_in';
    const PAGEVIEWS_ALL = 'pageviews_all';

    public static function allCriteriaConcatenated($glue = ',')
    {
        return implode($glue, self::getValues());
    }

    public static function getArticles(NewsletterCriteria $criteria, int $daysSpan, ?int $articlesCount = null): Collection
    {
        $start = Carbon::now()->subDays($daysSpan);

        switch ($criteria->getValue()) {
            case self::TIMESPENT_ALL:
                return ArticleTimespent::mostReadArticles($start, 'sum', $articlesCount);
            case self::TIMESPENT_SUBSCRIBERS:
                return ArticleTimespent::mostReadArticles($start, 'subscribers', $articlesCount);
            case self::TIMESPENT_SIGNED_IN:
                return ArticleTimespent::mostReadArticles($start, 'signed_in', $articlesCount);

            case self::PAGEVIEWS_ALL:
                return ArticlePageviews::mostReadArticles($start, 'sum', $articlesCount);
            case self::PAGEVIEWS_SIGNED_IN:
                return ArticlePageviews::mostReadArticles($start, 'signed_in', $articlesCount);
            case self::PAGEVIEWS_SUBSCRIBERS:
                return ArticlePageviews::mostReadArticles($start, 'subscribers', $articlesCount);

            case self::CONVERSIONS:
                return Conversion::mostReadArticleIdsByTotalPayment($start, $articlesCount);
            case self::AVERAGE_PAYMENT:
                return Conversion::mostReadArticleIdsByAveragePayment($start, $articlesCount);
            default:
                throw new Exception('unknown article criteria ' . $criteria->getValue());
        }
    }


    /**
     * @param NewsletterCriteria $criteria
     * @param int                $daysSpan
     *
     * @return array of articles (containing only external_id and url attributes)
     */
    public static function getCachedArticles(NewsletterCriteria $criteria, int $daysSpan): array
    {
        $tag = 'top_articles';
        $key = $tag . '|' . $criteria->getValue() . '|' . $daysSpan;

        return Cache::tags($tag)->remember($key, 10, function () use ($criteria, $daysSpan) {
            return self::getArticles($criteria, $daysSpan)->map(function ($article) {
                $item = new \stdClass();
                $item->external_id = $article->external_id;
                $item->url = $article->url;
                return $item;
            })->toArray();
        });
    }
}
