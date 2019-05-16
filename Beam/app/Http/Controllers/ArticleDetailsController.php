<?php

namespace App\Http\Controllers;

use App\Article;
use App\Helpers\Journal\JournalHelpers;
use App\Helpers\Colors;
use App\Helpers\Journal\JournalInterval;
use App\Http\Resources\ArticleResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;

class ArticleDetailsController extends Controller
{
    private const ALLOWED_INTERVALS = 'today,7days,30days,all';

    private $journal;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    public function variantsHistogram(Article $article, Request $request)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:' . self::ALLOWED_INTERVALS,
            'type' => 'required|in:title,image',
        ]);

        $type = $request->get('type');
        $groupBy = $type === 'title' ? 'title_variant' : 'image_variant';

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));
        $journalInterval = JournalInterval::from($tz, $request->get('interval'), $article);

        $data = $this->histogram($article, $journalInterval, $groupBy, function (AggregateRequest $request) {
            $request->addFilter('derived_referer_medium', 'internal');
            $request->addInverseFilter('explicit_referer_medium', 'mpm');
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
                'labels' => $variantTitles->pluck('title')->map(function ($title) {
                    return html_entity_decode($title, ENT_QUOTES);
                })->toArray(),
            ];
        }
        return response()->json($data);
    }

    public function timeHistogram(Article $article, Request $request)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:' . self::ALLOWED_INTERVALS,
            'events.*' => 'in:conversions'
        ]);

        $eventOptions = $request->get('events', []);

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));
        $journalInterval = JournalInterval::from($tz, $request->get('interval'), $article);

        $data = $this->histogram($article, $journalInterval, 'derived_referer_medium');
        $data['colors'] = Colors::refererMediumTagsToColors($data['tags']);
        $data['events'] = [];

        if (in_array('conversions', $eventOptions, false)) {
            $conversions = $article->conversions()
                ->whereBetween('paid_at', [$journalInterval->timeAfter->tz('UTC'), $journalInterval->timeBefore->tz('UTC')])
                ->get();

            foreach ($conversions as $conversion) {
                $data['events'][] = (object) [
                    'color' => '#651067',
                    'date' => $conversion->paid_at->toIso8601ZuluString(),
                    'title' => "{$conversion->amount} {$conversion->currency}"
                ];
            }
        }

        return response()->json($data);
    }

    private function histogram(Article $article, JournalInterval $journalInterval, $groupBy, callable $addConditions = null)
    {
        $journalRequest = (new AggregateRequest('pageviews', 'load'))
            ->addFilter('article_id', $article->external_id)
            ->setTime($journalInterval->timeAfter, $journalInterval->timeBefore)
            ->setTimeHistogram($journalInterval->intervalText, '0h')
            ->addGroup($groupBy);

        if ($addConditions) {
            $addConditions($journalRequest);
        }

        $currentRecords = collect($this->journal->count($journalRequest));

        $tags = [];
        foreach ($currentRecords as $records) {
            $tags[] = $records->tags->$groupBy;
        }

        // Values might be missing in time histogram, therefore fill all tags with 0s by default
        $results = [];
        $timeIterator = JournalHelpers::getTimeIterator($journalInterval->timeAfter, $journalInterval->intervalMinutes);

        while ($timeIterator->lessThan($journalInterval->timeBefore)) {
            $zuluDate = $timeIterator->toIso8601ZuluString();
            $results[$zuluDate] = collect($tags)->mapWithKeys(function ($item) {
                return [$item => 0];
            });
            $results[$zuluDate]['Date'] = $zuluDate;

            $timeIterator->addMinutes($journalInterval->intervalMinutes);
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
            'intervalMinutes' => $journalInterval->intervalMinutes,
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
            if (isset($result->tags->title_variant)) {
                $titleVariants[$result->tags->title_variant] = true;
            }

            if (isset($result->tags->image_variant)) {
                $imageVariants[$result->tags->image_variant] = true;
            }
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
