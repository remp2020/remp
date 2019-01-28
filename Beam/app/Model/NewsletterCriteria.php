<?php
namespace App\Model;

use App\Article;
use App\Author;
use Cache;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use MabeEnum\Enum;

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
    const BOOKMARKS = 'bookmarks';

    public static function allCriteriaConcatenated($glue = ',')
    {
        return implode($glue, self::getValues());
    }

    public function getArticles(int $daysSpan, ?int $articlesCount = null, array $ignoreAuthors = []): Collection
    {
        $start = Carbon::now()->subDays($daysSpan);

        $query = Article::distinct();

        switch ($this->getValue()) {
            case self::TIMESPENT_ALL:
                $query->mostReadByTimespent($start, 'sum', $articlesCount);
                break;
            case self::TIMESPENT_SUBSCRIBERS:
                $query->mostReadByTimespent($start, 'subscribers', $articlesCount);
                break;
            case self::TIMESPENT_SIGNED_IN:
                $query->mostReadByTimespent($start, 'signed_in', $articlesCount);
                break;

            case self::PAGEVIEWS_ALL:
                $query->mostReadByPageviews($start, 'sum', $articlesCount);
                break;
            case self::PAGEVIEWS_SUBSCRIBERS:
                $query->mostReadByPageviews($start, 'subscribers', $articlesCount);
                break;
            case self::PAGEVIEWS_SIGNED_IN:
                $query->mostReadByPageviews($start, 'signed_in', $articlesCount);
                break;

            case self::CONVERSIONS:
                $query->mostReadByTotalPaymentAmount($start, $articlesCount);
                break;
            case self::AVERAGE_PAYMENT:
                $query->mostReadByAveragePaymentAmount($start, $articlesCount);
                break;
            case self::BOOKMARKS:
                throw new Exception('not implemented');
            default:
                throw new Exception('unknown article criteria ' . $this->getValue());
        }

        $ignoreAuthorIds = Author::whereIn('name', $ignoreAuthors)->get()->pluck('id')->toArray();
        return $query->ignoreAuthorIds($ignoreAuthorIds)->get();
    }


    /**
     * @param int                $daysSpan
     * @param array              $ignoreAuthors
     *
     * @return array of articles (containing only external_id and url attributes)
     */
    public function getCachedArticles(int $daysSpan, array $ignoreAuthors = []): array
    {
        $tag = 'top_articles';
        $key = $tag . '|' . $this->getValue() . '|' . $daysSpan;

        return Cache::tags($tag)->remember($key, 10, function () use ($daysSpan, $ignoreAuthors) {
            return $this->getArticles($daysSpan, null, $ignoreAuthors)->map(function ($article) {
                $item = new \stdClass();
                $item->external_id = $article->external_id;
                $item->url = $article->url;
                return $item;
            })->toArray();
        });
    }
}
