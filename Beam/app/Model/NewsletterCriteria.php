<?php
namespace App\Model;

use App\Article;
use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Conversion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Recurr\Exception;

class NewsletterCriteria
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
        return implode($glue, [
            self::PAGEVIEWS_ALL,
            self::PAGEVIEWS_SIGNED_IN,
            self::PAGEVIEWS_SUBSCRIBERS,
            self::TIMESPENT_ALL,
            self::TIMESPENT_SIGNED_IN,
            self::TIMESPENT_SUBSCRIBERS,
            self::CONVERSIONS,
            self::AVERAGE_PAYMENT,
        ]);
    }

    public static function getArticles(string $criteria, int $daysSpan, $articlesCount = null): Collection
    {
        $start = Carbon::now()->subDays($daysSpan);

        switch ($criteria) {
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
                throw new Exception('unknown article criteria ' . $criteria);
        }
    }
}
