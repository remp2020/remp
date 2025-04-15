<?php
namespace Remp\BeamModule\Model;

use Cache;
use Exception;
use Illuminate\Support\Collection;
use Remp\BeamModule\Helpers\Misc;
use Remp\BeamModule\Model\Newsletter\NewsletterCriterionEnum;

class NewsletterCriterion
{
    public function __construct(private NewsletterCriterionEnum $selectedCriterion)
    {
    }

    public static function allCriteriaConcatenated($glue = ',')
    {
        return implode($glue, array_column(NewsletterCriterionEnum::cases(), 'value'));
    }

    public function getArticles(
        string $timespan,
        ?int $articlesCount = null,
        array $ignoreAuthors = [],
        array $ignoreContentTypes = []
    ): Collection {
        $start = Misc::timespanInPast($timespan);

        $query = Article::distinct();

        switch ($this->selectedCriterion) {
            case NewsletterCriterionEnum::TimespentAll:
                $query->mostReadByTimespent($start, 'sum', $articlesCount);
                break;
            case NewsletterCriterionEnum::TimespentSubscribers:
                $query->mostReadByTimespent($start, 'subscribers', $articlesCount);
                break;
            case NewsletterCriterionEnum::TimespentSignedIn:
                $query->mostReadByTimespent($start, 'signed_in', $articlesCount);
                break;

            case NewsletterCriterionEnum::PageViewsAll:
                $query->mostReadByPageviews($start, 'sum', $articlesCount);
                break;
            case NewsletterCriterionEnum::PageViewsSubscribers:
                $query->mostReadByPageviews($start, 'subscribers', $articlesCount);
                break;
            case NewsletterCriterionEnum::PageViewsSignedIn:
                $query->mostReadByPageviews($start, 'signed_in', $articlesCount);
                break;

            case NewsletterCriterionEnum::Conversions:
                $query->mostReadByTotalPaymentAmount($start, $articlesCount);
                break;
            case NewsletterCriterionEnum::AveragePayment:
                $query->mostReadByAveragePaymentAmount($start, $articlesCount);
                break;
            case NewsletterCriterionEnum::Bookmarks:
                throw new Exception('not implemented');
            default:
                throw new Exception('unknown article criterion ' . $this->selectedCriterion->value);
        }

        // Do not consider older articles
        $query->publishedBetween($start);

        $ignoreAuthorIds = Author::whereIn('name', $ignoreAuthors)->pluck('id')->toArray();
        return $query
            ->ignoreAuthorIds($ignoreAuthorIds)
            ->ignoreContentTypes($ignoreContentTypes)
            ->get();
    }


    /**
     * @param string $timespan
     * @param array $ignoreAuthors
     *
     * @return array of articles (containing only external_id and url attributes)
     */
    public function getCachedArticles(string $timespan, array $ignoreAuthors = [], array $ignoreContentTypes = []): array
    {
        $tag = 'top_articles';
        $key = $tag . '|' . $this->selectedCriterion->value . '|' . $timespan;

        return Cache::tags($tag)->remember($key, 300, function () use ($timespan, $ignoreAuthors, $ignoreContentTypes) {
            return $this->getArticles($timespan, null, $ignoreAuthors, $ignoreContentTypes)->map(function ($article) {
                $item = new \stdClass();
                $item->external_id = $article->external_id;
                $item->url = $article->url;
                return $item;
            })->toArray();
        });
    }
}
