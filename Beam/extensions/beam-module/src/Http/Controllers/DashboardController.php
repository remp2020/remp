<?php

namespace Remp\BeamModule\Http\Controllers;

use Remp\BeamModule\Model\DashboardArticle;
use Illuminate\Support\Arr;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Helpers\Journal\JournalHelpers;
use Remp\BeamModule\Helpers\Colors;
use Remp\BeamModule\Helpers\Journal\JournalInterval;
use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigCategory;
use Remp\BeamModule\Model\Config\ConfigNames;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\BeamModule\Model\Config\ConversionRateConfig;
use Remp\BeamModule\Model\Snapshots\SnapshotHelpers;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Log;
use Remp\Journal\AggregateRequest;
use Remp\Journal\ConcurrentsRequest;
use Remp\Journal\JournalContract;
use Remp\Journal\JournalException;

class DashboardController extends Controller
{
    private const NUMBER_OF_ARTICLES = 30;

    private $journal;

    private $journalHelper;

    private $snapshotHelpers;

    private $selectedProperty;

    public function __construct(
        JournalContract $journal,
        SnapshotHelpers $snapshotHelpers,
        SelectedProperty $selectedProperty,
    ) {
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
        $this->snapshotHelpers = $snapshotHelpers;
        $this->selectedProperty = $selectedProperty;
    }

    public function index()
    {
        return $this->dashboardView('beam::dashboard.index');
    }

    public function public()
    {
        return $this->dashboardView('beam::dashboard.public');
    }

    private function dashboardView($template)
    {
        $conversionRateConfig = ConversionRateConfig::build();

        $data = [
            'options' => $this->options(),
            'conversionRateMultiplier' => $conversionRateConfig->getMultiplier(),
        ];

        if (!config('beam.disable_token_filtering')) {
            $data['accountPropertyTokens'] = $this->selectedProperty->selectInputData();
        }

        $externalEvents = [];
        try {
            foreach ($this->journalHelper->eventsCategoriesActions() as $item) {
                $externalEvents[] = (object) [
                    'text' => $item->category . ':' . $item->action,
                    'value' => $item->category . JournalHelpers::CATEGORY_ACTION_SEPARATOR . $item->action,
                ];
            }
        } catch (JournalException | ClientException | RequestException $exception) {
            // if Journal is down, do not crash, but allowed page to be rendered (so user can switch to other page)
            Log::error($exception->getMessage());
        }
        $data['externalEvents'] = $externalEvents;

        return view($template, $data);
    }

    public function options(): array
    {
        $options = [];
        foreach (Config::ofCategory(ConfigCategory::CODE_DASHBOARD)->get() as $config) {
            $options[$config->name] = Config::loadByName($config->name);
        }

        // Additional options
        $options['dashboard_frontpage_referer_of_properties'] = array_values(Config::loadAllPropertyConfigs(ConfigNames::DASHBOARD_FRONTPAGE_REFERER));
        $options['article_traffic_graph_show_interval_7d'] = config('beam.article_traffic_graph_show_interval_7d');
        $options['article_traffic_graph_show_interval_30d'] = config('beam.article_traffic_graph_show_interval_30d');

        return $options;
    }

    private function getJournalParameters($interval, $tz)
    {
        switch ($interval) {
            case 'today':
                return [Carbon::tomorrow($tz), Carbon::today($tz), '20m', 20];
            case '7days':
                return [Carbon::tomorrow($tz), Carbon::today($tz)->subDays(6), '1h', 60];
            case '30days':
                return [Carbon::tomorrow($tz), Carbon::today($tz)->subDays(29), '2h', 120];
            default:
                throw new InvalidArgumentException("Parameter 'interval' must be one of the [today,7days,30days] values, instead '$interval' provided");
        }
    }

    /**
     * Return the time histogram for articles.
     *
     * Note: This action is cached. To improve response time especially for
     * longer time intervals and first request, consider to preheat cache
     * by calling this action from CLI command.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function timeHistogramNew(Request $request)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:today,7days,30days',
            'settings.compareWith' => 'required|in:last_week,average'
        ]);

        $settings = $request->get('settings');

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));
        $interval = $request->get('interval');
        $journalInterval = new JournalInterval($tz, $interval, null, ['today', '7days', '30days']);

        $from = $journalInterval->timeAfter;
        $to = $journalInterval->timeBefore;

        // for today histogram we need data not older than 1 minute
        if ($interval === 'today') {
            $journalInterval->cacheTTL = 60;
        }

        $toNextDayStart = (clone $to)->tz($tz)->addDay()->startOfDay();
        $intervalMinutes = $journalInterval->intervalMinutes;

        $currentData = $this->snapshotHelpers->concurrentsHistogram($journalInterval, null, true);

        $tags = [];
        foreach ($currentData as $item) {
            $tags[$this->itemTag($item)] = true;
        }

        // Compute shadow values for today and 7-days intervals
        $shadowRecords = [];
        if ($interval !== '30days') {
            $numberOfAveragedWeeks = $settings['compareWith'] === 'average' ? 4 : 1;

            for ($i = 1; $i <= $numberOfAveragedWeeks; $i++) {
                $shadowFrom = (clone $from)->subWeeks($i);
                $shadowTo = $toNextDayStart->copy()->subWeeks($i);

                // If there was a time shift, remember time needs to be adjusted by the timezone difference
                $diff = $shadowFrom->diff($from);
                $hourDifference = $diff->invert === 0 ? $diff->h : - $diff->h;

                $shadowInterval = clone $journalInterval;
                $shadowInterval->timeAfter = $shadowFrom;
                $shadowInterval->timeBefore = $shadowTo;

                // shadow values could be cached for 24 hours
                $shadowInterval->cacheTTL = 86400;

                foreach ($this->snapshotHelpers->concurrentsHistogram($shadowInterval) as $item) {
                    // we want to plot previous results on same points as current ones,
                    // therefore add week which was subtracted when data was queried
                    $correctedDate = Carbon::parse($item->time)
                        ->addWeeks($i)
                        ->addHours($hourDifference);

                    if ($correctedDate->lt($from) || $correctedDate->gt($toNextDayStart)) {
                        // some days might be longer (e.g. time-shift)
                        // therefore we do want to map all values to current week
                        // and avoid those which aren't
                        continue;
                    }

                    $correctedDate = $correctedDate->toIso8601ZuluString();

                    $currentTag = $this->itemTag($item);
                    $tags[$currentTag] = true;

                    if (!array_key_exists($correctedDate, $shadowRecords)) {
                        $shadowRecords[$correctedDate] = [];
                    }
                    if (!array_key_exists($currentTag, $shadowRecords[$correctedDate])) {
                        $shadowRecords[$correctedDate][$currentTag] = collect();
                    }
                    $shadowRecords[$correctedDate][$currentTag]->push($item->count);
                }
            }

            // Shadow records might not be in the correct order (if some key is missing from particular week, it's added at the end)
            // therefore reorder
            ksort($shadowRecords);
        }

        // Get tags
        $tags = array_keys($tags);
        $emptyValues = [];
        $totalTagCounts = [];
        foreach ($tags as $tag) {
            $emptyValues[$tag] = 0;
            $totalTagCounts[$tag] = 0;
        }

        $results = [];
        $shadowResults = [];
        $shadowResultsSummed = [];

        // Save current results
        foreach ($currentData as $item) {
            $zuluTime = Carbon::parse($item->time)->toIso8601ZuluString();
            if (!array_key_exists($zuluTime, $results)) {
                $results[$zuluTime] = array_merge($emptyValues, ['Date' => $zuluTime]);
            }
            $tag = $this->itemTag($item);
            $results[$zuluTime][$tag] += $item->count;
            $totalTagCounts[$tag] += $item->count;
        }

        // Save shadow results
        foreach ($shadowRecords as $date => $tagsAndValues) {
            foreach ($tagsAndValues as $tag => $values) {
                if (!array_key_exists($date, $shadowResults)) {
                    $shadowResults[$date] = $emptyValues;
                    $shadowResults[$date]['Date'] = $date;
                }

                if (!array_key_exists($date, $shadowResultsSummed)) {
                    $shadowResultsSummed[$date]['Date'] = $date;
                    $shadowResultsSummed[$date]['value'] = 0;
                }

                $avg = (int) round($values->avg());
                $shadowResults[$date][$tag] = $avg;
                $shadowResultsSummed[$date]['value'] += $avg;
            }
        }

        $orderedTags = Colors::orderRefererMediumTags($totalTagCounts);

        $jsonResponse = [
            'results' => array_values($results),
            'previousResults' => array_values($shadowResults),
            'previousResultsSummed' => array_values($shadowResultsSummed),
            'intervalMinutes' => $intervalMinutes,
            'tags' => $orderedTags,
            'colors' => array_values(Colors::assignColorsToMediumRefers($orderedTags)),
            'events' => $this->loadExternalEvents($journalInterval, $request->get('externalEvents', []))
        ];

        if ($interval === 'today') {
            $jsonResponse['maxDate'] = $toNextDayStart->toIso8601ZuluString();
        }

        return response()->json($jsonResponse);
    }

    private function itemTag($item): string
    {
        return $this->journalHelper->refererMediumLabel($item->referer_medium);
    }

    private function getRefererMediumFromJournalRecord($record)
    {
        return $this->journalHelper->refererMediumLabel($record->tags->derived_referer_medium);
    }

    public function timeHistogram(Request $request)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:today,7days,30days',
            'settings.compareWith' => 'required|in:last_week,average'
        ]);

        $settings = $request->get('settings');

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));
        $interval = $request->get('interval');
        [$timeBefore, $timeAfter, $intervalText, $intervalMinutes] = $this->getJournalParameters($interval, $tz);

        $endOfDay = (clone $timeAfter)->tz($tz)->endOfDay();

        $journalRequest = new AggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($intervalText);
        $journalRequest->addGroup('derived_referer_medium');
        $currentRecords = collect($this->journal->count($journalRequest));

        // Get all tags
        $tags = [];
        $totalCounts = [];
        foreach ($currentRecords as $record) {
            $tag = $this->getRefererMediumFromJournalRecord($record);
            $totalCounts[$tag] = 0;
            $tags[$tag] = true;
        }

        // Compute shadow values for today and 7-days intervals
        $shadowRecords = [];
        if ($interval !== '30days') {
            $numberOfAveragedWeeks = $settings['compareWith'] === 'average' ? 4 : 1;

            for ($i = 1; $i <= $numberOfAveragedWeeks; $i++) {
                $from = (clone $timeAfter)->subWeeks($i);
                $to = (clone $timeBefore)->subWeeks($i);

                // If there was a time shift, remember time needs to be adjusted by the timezone difference
                $diff = $from->diff($timeAfter);
                $hourDifference = $diff->invert === 0 ? $diff->h : - $diff->h;

                foreach ($this->pageviewRecordsBasedOnRefererMedium($from, $to, $intervalText) as $record) {
                    $currentTag = $this->getRefererMediumFromJournalRecord($record);
                    // update tags
                    $tags[$currentTag] = true;

                    if (!isset($record->time_histogram)) {
                        continue;
                    }

                    foreach ($record->time_histogram as $timeValue) {
                        // we want to plot previous results on same points as current ones,
                        // therefore add week which was subtracted when data was queried
                        $correctedDate = Carbon::parse($timeValue->time)
                            ->addWeeks($i)
                            ->addHours($hourDifference)
                            ->toIso8601ZuluString();

                        if (!array_key_exists($correctedDate, $shadowRecords)) {
                            $shadowRecords[$correctedDate] = [];
                        }
                        if (!array_key_exists($currentTag, $shadowRecords[$correctedDate])) {
                            $shadowRecords[$correctedDate][$currentTag] = collect();
                        }
                        $shadowRecords[$correctedDate][$currentTag]->push($timeValue->value);
                    }
                }
            }
        }

        $tags = array_keys($tags);

        // Values might be missing in time histogram, therefore fill all tags with 0s by default
        $results = [];
        $shadowResults = [];
        $shadowResultsSummed = [];
        $timeIterator = JournalHelpers::getTimeIterator($timeAfter, $intervalMinutes);

        $emptyValues = collect($tags)->mapWithKeys(function ($item) {
            return [$item => 0];
        })->toArray();

        while ($timeIterator->lessThan($timeBefore)) {
            $zuluDate = $timeIterator->toIso8601ZuluString();

            $results[$zuluDate] = $emptyValues;
            $results[$zuluDate]['Date'] = $zuluDate;

            if (count($shadowRecords) > 0) {
                $shadowResults[$zuluDate] = $emptyValues;
                $shadowResults[$zuluDate]['Date'] = $shadowResultsSummed[$zuluDate]['Date'] = $zuluDate;
                $shadowResultsSummed[$zuluDate]['value'] = 0;
            }

            $timeIterator->addMinutes($intervalMinutes);
        }

        // Save current results
        foreach ($currentRecords as $record) {
            if (!isset($record->time_histogram)) {
                continue;
            }
            $currentTag = $this->getRefererMediumFromJournalRecord($record);

            foreach ($record->time_histogram as $timeValue) {
                $results[$timeValue->time][$currentTag] = $timeValue->value;
            }
            $totalCounts[$currentTag] += $record->count;
        }

        // Save shadow results
        foreach ($shadowRecords as $date => $tagsAndValues) {
            // check if all keys exists - e.g. some days might be longer (time-shift)
            // therefore we do want to map all values to current week
            if (!array_key_exists($date, $shadowResults)) {
                continue;
            }

            foreach ($tagsAndValues as $tag => $values) {
                $avg = (int) round($values->avg());

                $shadowResults[$date][$tag] = $avg;
                $shadowResultsSummed[$date]['value'] += $avg;
            }
        }

        // What part of current results we should draw (omit future 0 values)
        $numberOfCurrentValues = (int) floor((Carbon::now($tz)->getTimestamp() - $timeAfter->getTimestamp()) / ($intervalMinutes * 60));
        if ($interval === 'today') {
            // recompute last interval - it's not fully loaded, yet we want at least partial results
            // to see the current traffic
            $results = collect(array_values($results))->take($numberOfCurrentValues + 1);
            $unfinished = $results->pop();
            $unfinishedDate = Carbon::parse($unfinished['Date']);

            $current = Carbon::now();

            // if recent interval is bigger than 120 seconds, recompute its values and add it back to results
            // smaller intervals do not make good approximations
            if ((clone $current)->subSeconds(120)->gt($unfinishedDate)) {
                $increaseRate = ($intervalMinutes * 60) / ($current->getTimestamp() - $unfinishedDate->getTimestamp());
                foreach ($tags as $tag) {
                    $unfinished[$tag] = (int)($unfinished[$tag] * $increaseRate);
                }
                $unfinished['Date'] = $current->subMinutes($intervalMinutes)->toIso8601ZuluString();
                $unfinished['_unfinished'] = true;

                $results->push($unfinished);
            }
        } else {
            $results = collect(array_values($results))->take($numberOfCurrentValues);
        }

        $events = $this->loadExternalEvents(new JournalInterval($tz, $interval, null, ['today', '7days', '30days']), $request->get('externalEvents', []));

        $orderedTags = Colors::orderRefererMediumTags($totalCounts);

        $jsonResponse = [
            'intervalMinutes' => $intervalMinutes,
            'results' => $results,
            'previousResults' => array_values($shadowResults),
            'previousResultsSummed' => array_values($shadowResultsSummed),
            'tags' => $orderedTags,
            'colors' => array_values(Colors::assignColorsToMediumRefers($orderedTags)),
            'events' => $events,
        ];

        if ($interval === 'today') {
            $jsonResponse['maxDate'] = $endOfDay->subMinutes($intervalMinutes)->toIso8601ZuluString();
        }

        return response()->json($jsonResponse);
    }

    private function pageviewRecordsBasedOnRefererMedium(
        Carbon $timeAfter,
        Carbon $timeBefore,
        string $interval
    ) {
        $journalRequest = new AggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($interval);
        $journalRequest->addGroup('derived_referer_medium');

        return collect($this->journal->count($journalRequest));
    }

    private function loadExternalEvents(JournalInterval $journalInterval, $requestedExternalEvents): array
    {
        $eventData = $this->journalHelper->loadEvents($journalInterval, $requestedExternalEvents);

        $tags = [];
        $articles = [];
        $events = [];

        foreach ($eventData as $eventItem) {
            $title = $eventItem->category . ':' . $eventItem->action;
            $tags[$title] = true;

            $date = Carbon::parse($eventItem->system->time);

            $text = "<b>$title</b><br />" .
                'At: ' . $date->copy()->tz($journalInterval->tz)->format('Y-m-d H:i');

            if (isset($eventItem->article_id)) {
                $article = $articles[$eventItem->article_id] ?? null;
                if (!$article) {
                    $article = Article::where('external_id', $eventItem->article_id)->first();
                }

                if ($article) {
                    $articles[$eventItem->article_id] = $article;
                    $url = route('articles.show', $article->id);
                    $articleTitle = Str::limit($article->title, 50);
                    $text .= '<br />Article: <a style="text-decoration: underline; color: #fff" href="'. $url .'">' . $articleTitle . '</b>';
                }
            }

            $events[] = (object) [
                'date' => $date->toIso8601ZuluString(),
                'id' => $title,
                'title' => $text,
            ];
        }

        $colors = Colors::assignColorsToGeneralTags(array_keys($tags));
        foreach ($events as $event) {
            $event->color = $colors[$event->id];
        }

        return $events;
    }

    public function mostReadArticles(Request $request)
    {
        $request->validate([
            'settings.onlyTrafficFromFrontPage' => 'required|boolean'
        ]);

        $settings = $request->get('settings');

        $timeBefore = Carbon::now();

        $frontpageReferers = (array) Config::loadByName(ConfigNames::DASHBOARD_FRONTPAGE_REFERER);
        if (!$frontpageReferers) {
            $frontpageReferers = array_values(Config::loadAllPropertyConfigs(ConfigNames::DASHBOARD_FRONTPAGE_REFERER));
        }
        $filterFrontPage = $frontpageReferers && $settings['onlyTrafficFromFrontPage'];

        // records are already sorted
        $records = $this->journalHelper->currentConcurrentsCount(function (ConcurrentsRequest $req) use ($filterFrontPage, $frontpageReferers) {
            $req->addGroup('article_id', 'canonical_url');
            if ($filterFrontPage) {
                $req->addFilter('derived_referer_host_with_path', ...$frontpageReferers);
            }
        }, $timeBefore);

        $computerConcurrents = $this->journalHelper->currentConcurrentsCount(function (ConcurrentsRequest $req) use ($filterFrontPage, $frontpageReferers) {
            $req->addFilter('derived_ua_device', 'Computer');

            if ($filterFrontPage) {
                $req->addFilter('derived_referer_host_with_path', ...$frontpageReferers);
            }
        }, $timeBefore);
        $computerConcurrentsCount = $computerConcurrents->first()->count;

        $articlesIds = array_filter($records->pluck('tags.article_id')->toArray());
        $articleQuery = Article::with(['dashboardArticle', 'conversions'])
            ->whereIn(
                'external_id',
                array_map('strval', $articlesIds)
            );

        $articles = [];
        foreach ($articleQuery->get() as $article) {
            $articles[$article->external_id] = $article;
        }

        $topPages = [];
        $articleCounter = 0;
        $totalConcurrents = 0;
        foreach ($records as $record) {
            $totalConcurrents += $record->count;

            if ($articleCounter >= self::NUMBER_OF_ARTICLES) {
                continue;
            }

            $article = null;
            if ($record->tags->article_id) {
                // check if the article is recognized
                $article = $articles[$record->tags->article_id] ?? null;
                if (!$article) {
                    continue;
                }
                $key = $record->tags->article_id;
            } else {
                $key = $record->tags->canonical_url ?? '';
            }

            // Some articles might have multiple canonical URLs. Merge them.
            if (isset($topPages[$key])) {
                $obj = $topPages[$key];
                $obj->count += $record->count;
            } else {
                $obj = new \stdClass();
                $obj->count = $record->count;
                $obj->external_article_id = $record->tags->article_id;
                if ($record->tags->article_id) {
                    $articleCounter++;
                }
            }

            if ($article) {
                $obj->landing_page = false;
                $obj->title = $article->title;
                $obj->published_at = $article->published_at->toAtomString();
                $obj->conversions_count = (clone $article)->conversions->count();
                $obj->article = $article;
            } else {
                $obj->title = $record->tags->canonical_url ?: 'Landing page / Other pages';
                $obj->landing_page = true;
            }

            $topPages[$key] = $obj;
        }

        $mobileConcurrentsPercentage = 0;
        if ($totalConcurrents > 0 && $computerConcurrentsCount > 0) {
            $mobileConcurrentsPercentage = (($totalConcurrents - $computerConcurrentsCount) / $totalConcurrents) * 100;
        } elseif ($totalConcurrents > 0 && $computerConcurrentsCount === 0) {
            $mobileConcurrentsPercentage = null;
        }

        usort($topPages, function ($a, $b) {
            return -($a->count <=> $b->count);
        });
        $topPages = array_slice($topPages, 0, 30);

        // Add chart data into top articles
        $tz = new \DateTimeZone('UTC');
        $journalInterval = new JournalInterval($tz, '1day');

        $topPages = $this->addOverviewChartData($topPages, $journalInterval);

        $topArticles = collect($topPages)->filter(function ($item) {
            return !empty($item->external_article_id);
        })->pluck('article');

        $this->updateDasboardArticle($topArticles);

        $externalIdsToUniqueUsersCount = $this->getUniqueBrowserCountData($topArticles);

        // Timespent is computed as average of timespent values 2 hours in the past
        $externalIdsToTimespent = $this->journalHelper->timespentForArticles(
            $topArticles,
            (clone $timeBefore)->subHours(2)
        );

        // Check for A/B titles/images for last 5 minutes
        $externalIdsToAbTestFlags = $this->journalHelper->abTestFlagsForArticles($topArticles, Carbon::now()->subMinutes(5));

        $conversionRateConfig = ConversionRateConfig::build();
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        foreach ($topPages as $item) {
            if ($item->external_article_id) {
                $secondsTimespent = $externalIdsToTimespent->get($item->external_article_id, 0);
                $item->avg_timespent_string = $secondsTimespent >= 3600 ?
                    gmdate('H:i:s', $secondsTimespent) :
                    gmdate('i:s', $secondsTimespent);
                $item->unique_browsers_count = $externalIdsToUniqueUsersCount[$item->external_article_id];
                // Show conversion rate only for articles published in last 3 months
                if ($item->conversions_count !== 0 && $item->article->published_at->gte($threeMonthsAgo)) {
                    $item->conversion_rate = Article::computeConversionRate($item->conversions_count, $item->unique_browsers_count, $conversionRateConfig);
                }

                $item->has_title_test = $externalIdsToAbTestFlags[$item->external_article_id]->has_title_test ?? false;
                $item->has_image_test = $externalIdsToAbTestFlags[$item->external_article_id]->has_image_test ?? false;

                $item->url = route('articles.show', ['article' => $item->article->id]);
            }
        }

        return response()->json([
            'articles' => $topPages,
            'mobileConcurrentsPercentage' => $mobileConcurrentsPercentage,
            'totalConcurrents' => $totalConcurrents,
        ]);
    }

    private function updateDasboardArticle(Collection $articles)
    {
        $articleIds = $articles->pluck('id')->toArray();
        $dashboardArticles = DashboardArticle::whereIn('article_id', $articleIds)->get();

        $storedDashboardArticles = array_intersect($articleIds, $dashboardArticles->pluck('article_id')->toArray());
        if ($storedDashboardArticles) {
            DashboardArticle::whereIn('article_id', $storedDashboardArticles)
                ->update(['last_dashboard_time' => Carbon::now()]);
        }

        $unstoredDashboardArticles = array_diff($articleIds, $storedDashboardArticles);
        if ($unstoredDashboardArticles) {
            $data = [];
            $now = Carbon::now();
            foreach ($unstoredDashboardArticles as $articleId) {
                $data[] = [
                    'article_id' => $articleId,
                    'last_dashboard_time' => $now,
                    'created_at' => $now,
                ];
            }
            DashboardArticle::insert($data);
        }
    }

    /**
     * @param \stdClass[] $topPages
     * @param JournalInterval $journalInterval
     * @return \stdClass[]
     * @throws \Exception
     */
    private function addOverviewChartData(array $topPages, JournalInterval $journalInterval)
    {
        $articleIds = array_filter(Arr::pluck($topPages, 'article.external_id'));
        //no articles present in the topPages
        if (empty($articleIds)) {
            return $topPages;
        }

        $currentDataSource = config('beam.pageview_graph_data_source');

        switch ($currentDataSource) {
            case 'snapshots':
                $articlesChartData = $this->getOverviewChartDataFromSnapshots($articleIds, $journalInterval);
                break;
            case 'journal':
                $articlesChartData = $this->getOverviewChartDataFromJournal($articleIds, $journalInterval);
                break;
            default:
                throw new \Exception("unknown pageviews data source {$currentDataSource}");
        }

        //add chart data into topPages object
        $topPages = array_map(function ($topPage) use ($articlesChartData) {
            if (!isset($topPage->article) || !isset($articlesChartData[$topPage->article->external_id])) {
                return $topPage;
            }
            $topPage->chartData = $articlesChartData[$topPage->article->external_id];

            return $topPage;
        }, $topPages);

        return $topPages;
    }

    private function getOverviewChartDataFromSnapshots(array $articleIds, JournalInterval $journalInterval)
    {
        $records = $this->snapshotHelpers->concurrentArticlesHistograms($journalInterval, $articleIds);
        $recordsByArticleId = $records->groupBy('external_article_id');

        $resultsSkeleton = $this->prefillOverviewResults($journalInterval);
        $articlesChartData = [];

        // use zero-value timePoints as a base so we don't need to worry about missing snapshot values later
        $articleSkeleton = $this->createArticleSkeleton($resultsSkeleton);

        foreach ($recordsByArticleId as $articleId => $items) {
            $timePointToMatch = reset($resultsSkeleton)['Date'];
            $results = $articleSkeleton;

            foreach ($items as $item) {
                if ($item->timestamp < $resultsSkeleton[$timePointToMatch]['Timestamp']) {
                    // skip all the data items earlier that the current $timePoint
                    continue;
                }

                // move $timePoint iterator forward until it matches item's timestamp
                do {
                    $timePointToSet = $timePointToMatch;
                    $timePointToMatch = next($resultsSkeleton)['Date'] ?? null;
                } while ($timePointToMatch && $item->timestamp >= $resultsSkeleton[$timePointToMatch]['Timestamp']);

                // found the $timePoint, set the snapshot value
                $results[$timePointToSet] = [
                    't' => $resultsSkeleton[$timePointToSet]['Timestamp'],
                    'c' => (int) $item->count,
                ];

                if (!$timePointToMatch) {
                    break;
                }
            }

            $articlesChartData[$articleId] = array_values($results);
        }

        return $articlesChartData;
    }

    private function getUniqueBrowserCountData(Collection $articles): array
    {
        $articles = (clone $articles)->keyBy('external_id');
        $result = [];

        /** @var Article $article */
        foreach ($articles as $article) {
            if ($article->dashboardArticle && $article->dashboardArticle->unique_browsers) {
                $result[$article->external_id] = $article->dashboardArticle->unique_browsers;
                $articles->forget($article->external_id);
            }
        }

        // Check if we have stats for all articles. If we do, return immediately.
        if (!$articles->count()) {
            return $result;
        }

        // If some articles are missing the stats, make the realtime call.
        $realtimeResults = $this->journalHelper->uniqueBrowsersCountForArticles($articles);
        foreach ($realtimeResults as $externalArticleId => $count) {
            /** @var Article $article */
            $article = $articles->get($externalArticleId);
            $article->dashboardArticle()->updateOrCreate([], [
                'unique_browsers' => (int) $count,
            ]);
            $result[$externalArticleId] = (int) $count;
        }

        return $result;
    }

    private function getOverviewChartDataFromJournal(array $articleIds, JournalInterval $journalInterval)
    {
        $journalRequest = (new AggregateRequest('pageviews', 'load'))
            ->addFilter('article_id', ...$articleIds)
            ->addGroup('article_id')
            ->setTime($journalInterval->timeAfter, $journalInterval->timeBefore)
            ->setTimeHistogram($journalInterval->intervalText);

        $pageviewRecords = collect($this->journal->count($journalRequest));
        $resultsSkeleton = $this->prefillOverviewResults($journalInterval);
        $articleSkeleton = $this->createArticleSkeleton($resultsSkeleton);
        $articlesChartData = [];

        foreach ($pageviewRecords as $pageviewRecord) {
            if (!isset($pageviewRecord->time_histogram)) {
                continue;
            }

            $results = $articleSkeleton;
            foreach ($pageviewRecord->time_histogram as $timeValue) {
                $results[$timeValue->time]['c'] += $timeValue->value;
            }
            $articlesChartData[$pageviewRecord->tags->article_id] = array_values($results);
        }

        return $articlesChartData;
    }

    private function prefillOverviewResults(JournalInterval $journalInterval)
    {
        $timeIterator = JournalHelpers::getTimeIterator($journalInterval->timeAfter, $journalInterval->intervalMinutes);
        $results = [];

        while ($timeIterator->lessThan($journalInterval->timeBefore)) {
            $zuluDate = $timeIterator->toIso8601ZuluString();
            $results[$zuluDate]['Count'] = 0;
            $results[$zuluDate]['Date'] = $zuluDate;
            $results[$zuluDate]['Timestamp'] = $timeIterator->timestamp;

            $timeIterator->addMinutes($journalInterval->intervalMinutes);
        }

        return $results;
    }

    private function createArticleSkeleton(array $resultsSkeleton)
    {
        $articleSkeleton = [];
        foreach ($resultsSkeleton as $timePoint => $skeleton) {
            $articleSkeleton[$timePoint] = [
                't' => $skeleton['Timestamp'],
                'c' => 0,
            ];
        }

        return $articleSkeleton;
    }
}
