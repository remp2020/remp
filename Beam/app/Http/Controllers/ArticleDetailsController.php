<?php

namespace App\Http\Controllers;

use App\Article;
use App\Contracts\JournalHelpers;
use App\Helpers\Colors;
use App\Http\Resources\ArticleResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;

class ArticleDetailsController extends Controller
{
    private $journal;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    private function getIntervalDependingOnArticlePublishedDate(Article $article): array
    {
        $articleAgeInMins = Carbon::now()->diffInMinutes($article->published_at);
        if ($articleAgeInMins <= 60) { // 1 hour
            return ["5m", 5];
        } else if ($articleAgeInMins <= 60*24) { // 1 day
            return ["20m", 20];
        } else if ($articleAgeInMins <= 7*60*24) { // 7 days
            return ["1h", 60];
        } else if ($articleAgeInMins <= 30*60*24) { // 30 days
            return ["2h", 120];
        } else if ($articleAgeInMins <= 90*60*24) { // 90 days
            return ["3h", 180];
        } else if ($articleAgeInMins <= 180*60*24) { // 180 days
            return ["6h", 360];
        } else if ($articleAgeInMins <= 365*60*24) { // 1 year
            return ["12h", 720];
        } else { // 1+ year
            return ["24h", 1440];
        }
    }

    public function variantsHistogram(Article $article, Request $request)
    {
        $request->validate([
            'type' => 'required|in:title,image',
        ]);

        $type = $request->get('type');
        $groupBy = $type === 'title' ? 'title_variant' : 'image_variant';

        $data = $this->histogram($article, $request, $groupBy, function (AggregateRequest $request) {
            $request->addFilter('derived_referer_medium', 'internal');
        });
        $data['colors'] = Colors::abTestVariantTagsToColors($data['tags']);

        $tagToColor = [];
        for ($i = 0, $iMax = count($data['tags']); $i < $iMax; $i++) {
            $tagToColor[$data['tags'][$i]] = $data['colors'][$i];
        }

        $data['tagLabels'] = [];

        $articleTitles = $article
            ->articleTitles()
            ->whereIn('variant', $data['tags'])
            ->get()
            ->groupBy('variant');

        $data['events'] = [];

        foreach ($articleTitles as $variant => $variantTitles) {
            if ($variantTitles->count() > 1) {
                for ($i = 0; $i < $variantTitles->count() - 1; $i++) {
                    $oldTitle = $variantTitles[$i];
                    $newTitle = $variantTitles[$i+1];
                    $data['events'][] = (object) [
                        'color' => $tagToColor[$variant],
                        'date' => $newTitle->created_at->toIso8601ZuluString(),
                        'title' => "<b>{$variant} Title Variant Changed</b><br /><b>From:</b> {$oldTitle->title}<br /><b>To:</b> {$newTitle->title}"
                    ];
                }
            }

            $data['tagLabels'][$variant] = (object) [
                'color' => $tagToColor[$variant],
                'labels' => $variantTitles->pluck('title')->map(function($title){
                    return html_entity_decode($title, ENT_QUOTES);
                })->toArray(),
            ];
        }
        return response()->json($data);
    }

    public function timeHistogram(Article $article, Request $request)
    {
        $data = $this->histogram($article, $request, 'derived_referer_medium');
        $data['colors'] = Colors::refererMediumTagsToColors($data['tags']);
        return response()->json($data);
    }

    private function histogram(Article $article, Request $request, $groupBy, callable $addConditions = null)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:today,7days,30days,all',
        ]);

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));

        $interval = $request->get('interval');
        switch ($interval) {
            case 'today':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz);
                $intervalElastic = '20m';
                $intervalMinutes = 20;
                break;
            case '7days':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz)->subDays(6);
                $intervalElastic = '1h';
                $intervalMinutes = 60;
                break;
            case '30days':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz)->subDays(29);
                $intervalElastic = '2h';
                $intervalMinutes = 120;
                break;
            case 'all':
                $timeBefore = Carbon::now($tz);
                $timeAfter = (clone $article->published_at)->tz($tz);
                [$intervalElastic, $intervalMinutes] = $this->getIntervalDependingOnArticlePublishedDate($article);
                break;
            default:
                throw new InvalidArgumentException("Parameter 'interval' must be one of the [today,7days,30days] values, instead '$interval' provided");
        }

        $journalRequest = new AggregateRequest('pageviews', 'load');
        $journalRequest->addFilter('article_id', $article->external_id);
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($intervalElastic, '0h');
        $journalRequest->addGroup($groupBy);

        if ($addConditions) {
            $addConditions($journalRequest);
        }

        $currentRecords = collect($this->journal->count($journalRequest));

        // Get all tags
        $tags = [];
        foreach ($currentRecords as $records) {
            $tags[] = $records->tags->$groupBy;
        }

        // Values might be missing in time histogram, therefore fill all tags with 0s by default
        $results = [];
        $timeIterator = JournalHelpers::getTimeIterator($timeAfter, $intervalMinutes);

        while ($timeIterator->lessThan($timeBefore)) {
            $zuluDate = $timeIterator->toIso8601ZuluString();
            $results[$zuluDate] = collect($tags)->mapWithKeys(function ($item) {
                return [$item => 0];
            });
            $results[$zuluDate]['Date'] = $zuluDate;

            $timeIterator->addMinutes($intervalMinutes);
        }

        // Save results
        foreach ($currentRecords as $records) {
            if (!isset($records->time_histogram)) {
                continue;
            }
            $currentTag = $records->tags->$groupBy;

            foreach ($records->time_histogram as $timeValue) {
                $results[$timeValue->time][$currentTag] = $timeValue->value;
            }
        }
        $results = array_values($results);

        return [
            'publishedAt' => $article->published_at->toIso8601ZuluString(),
            'intervalMinutes' => $intervalMinutes,
            'results' => $results,
            'tags' => $tags
        ];
    }

    public function show(Article $article, Request $request)
    {
        $timeBefore = Carbon::now();
        $timeAfter = $article->published_at;

        $uniqueRequest = new AggregateRequest('pageviews', 'load');
        $uniqueRequest->setTimeAfter($timeAfter);
        $uniqueRequest->setTimeBefore($timeBefore);
        $uniqueRequest->addGroup('article_id', 'title_variant', 'image_variant');
        $uniqueRequest->addFilter('article_id', $article->external_id);
        $results = collect($this->journal->unique($uniqueRequest));

        $titleVariants = [];
        $imageVariants = [];
        foreach ($results as $result) {
            $titleVariants[$result->tags->title_variant] = true;
            $imageVariants[$result->tags->image_variant] = true;
        }

        $uniqueBrowsersCount = $results->sum('count');

        $conversionRate = $uniqueBrowsersCount == 0 ? 0 : ($article->conversions()->count() / $uniqueBrowsersCount) * 100;

        $conversionsSum = collect();
        foreach ($article->conversions as $conversions) {
            if (!$conversionsSum->has($conversions->currency)) {
                $conversionsSum[$conversions->currency] = 0;
            }
            $conversionsSum[$conversions->currency] += $conversions->amount;
        }

        $conversionsSum = $conversionsSum->map(function ($sum, $currency) {
            return number_format($sum, 2) . ' ' . $currency;
        })->values()->implode(', ');


        $newConversionsCount = $article->loadNewConversionsCount();
        $renewedConversionsCount = $article->loadRenewedConversionsCount();

        $pageviewsSubscribersToAllRatio =
            $article->pageviews_all == 0 ? 0 : ($article->pageviews_subscribers / $article->pageviews_all) * 100;

        return response()->format([
            'html' => view('articles.show', [
                'article' => $article,
                'pageviewsSubscribersToAllRatio' => $pageviewsSubscribersToAllRatio,
                'conversionRate' => $conversionRate,
                'conversionsSum' => $conversionsSum,
                'uniqueBrowsersCount' => $uniqueBrowsersCount,
                'newConversionsCount' => $newConversionsCount,
                'renewedConversionsCount' => $renewedConversionsCount,
                'dataFrom' => $request->input('data_from', 'now - 30 days'),
                'dataTo' => $request->input('data_to', 'now'),
                'hasTitleVariants' => count($titleVariants) > 1,
                'hasImageVariants' => count($imageVariants) > 1,
            ]),
            'json' => new ArticleResource($article)
        ]);
    }
}
