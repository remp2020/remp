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

    private $journalHelper;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
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
        $journalInterval = new JournalInterval($tz, $request->get('interval'), $article);

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
        $journalInterval = new JournalInterval($tz, $request->get('interval'), $article);

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

    public function showByParameter(Request $request, Article $article = null)
    {
        if (!$article) {
            $externalId = $request->input('external_id');
            $url = $request->input('url');

            if ($externalId) {
                $article = Article::where('external_id', $externalId)->first();
                if (!$article) {
                    abort(404, 'No article found for given external_id parameter');
                }
            } elseif ($url) {
                $article = Article::where('url', $url)->first();
                if (!$article) {
                    abort(404, 'No article found for given URL parameter');
                }
            } else {
                abort(404, 'Please specify either article ID, external_id or URL');
            }
        }

        return redirect()->route('articles.show', ['id' => $article->id]);
    }

    public function show(Request $request, Article $article = null)
    {
        if (!$article) {
            $externalId = $request->input('external_id');
            $url = $request->input('url');

            if ($externalId) {
                $article = Article::where('external_id', $externalId)->first();
                if (!$article) {
                    abort(404, 'No article found for given external_id parameter');
                }
            } elseif ($url) {
                $article = Article::where('url', $url)->first();
                if (!$article) {
                    abort(404, 'No article found for given URL parameter');
                }
            } else {
                abort(404, 'Please specify either article ID, external_id or URL');
            }
        }

        $conversionsSums = collect();
        foreach ($article->conversions as $conversions) {
            if (!$conversionsSums->has($conversions->currency)) {
                $conversionsSums[$conversions->currency] = 0;
            }
            $conversionsSums[$conversions->currency] += $conversions->amount;
        }
        $conversionsSums = $conversionsSums->map(function ($sum, $currency) {
            return number_format($sum, 2) . ' ' . $currency;
        })->values()->implode(', ');

        $pageviewsSubscribersToAllRatio =
            $article->pageviews_all == 0 ? 0 : ($article->pageviews_subscribers / $article->pageviews_all) * 100;

        $mediums = $this->journalHelper->derivedRefererMediumGroups()->mapWithKeys(function ($item) {
            return [$item => $item];
        });

        return response()->format([
            'html' => view('articles.show', [
                'article' => $article,
                'pageviewsSubscribersToAllRatio' => $pageviewsSubscribersToAllRatio,
                'conversionsSums' => $conversionsSums,
                'dataFrom' => $request->input('data_from', 'now - 30 days'),
                'dataTo' => $request->input('data_to', 'now'),
                'mediums' => $mediums,
                'mediumColors' => Colors::refererMediumTagsToColors($mediums, true),
                'visitedFrom' => $request->input('visited_from', 'now - 30 days'),
                'visitedTo' => $request->input('visited_to', 'now'),
            ]),
            'json' => new ArticleResource($article),
        ]);
    }

    public function dtReferers(Article $article, Request $request)
    {
        $length = $request->input('length');
        $start = $request->input('start');
        $orderOptions = $request->input('order');
        $draw = $request->input('draw');

        $visitedTo = Carbon::parse($request->input('visited_to'), $request->input('tz'))->tz('UTC');
        $visitedFrom = Carbon::parse($request->input('visited_from'), $request->input('tz'))->tz('UTC');

        $ar = (new AggregateRequest('pageviews', 'load'))
            ->setTime($visitedFrom, $visitedTo)
            ->addGroup('derived_referer_host_with_path', 'derived_referer_medium', 'derived_referer_source')
            ->addFilter('article_id', $article->external_id);

        $columns = $request->input('columns');
        foreach ($columns as $index => $column) {
            if (isset($column['search']['value'])) {
                $ar->addFilter($column['name'], ...explode(',', $column['search']['value']));
            }
        }

        $data = collect();
        $records = $this->journal->count($ar);

        // 'derived_referer_source' has priority over 'derived_referer_host_with_path'
        // since we do not want to distinguish between e.g. m.facebook.com and facebook.com, all should be categorized as one
        $aggregated = [];
        foreach ($records as $record) {
            $derivedMedium = $record->tags->derived_referer_medium;
            $derivedSource = $record->tags->derived_referer_source;
            $source = $record->tags->derived_referer_host_with_path;
            $count = $record->count;

            if (!array_key_exists($derivedMedium, $aggregated)) {
                $aggregated[$derivedMedium] = [];
            }

            $key = $source;
            if ($derivedSource) {
                $key = $derivedSource;
            }

            if (!array_key_exists($key, $aggregated[$derivedMedium])) {
                $aggregated[$derivedMedium][$key] = 0;
            }
            $aggregated[$derivedMedium][$key] += $count;
        }

        foreach ($aggregated as $medium => $mediumSources) {
            foreach ($mediumSources as $source => $count) {
                $data->push([
                    'derived_referer_medium' => $medium,
                    'source' => $source,
                    'visits_count' => $count,
                ]);
            }
        }

        if (count($orderOptions) > 0) {
            $option = $orderOptions[0];
            $orderColumn = $columns[$option['column']]['name'];
            $orderDirectionDesc = $option['dir'] === 'desc';
            $data = $data->sortBy($orderColumn, SORT_REGULAR, $orderDirectionDesc)->values();
        }

        $recordsTotal = $data->count();
        $data = $data->slice($start, $length)->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data
        ]);
    }
}
