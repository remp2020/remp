<?php

namespace App\Http\Controllers;

use App\Article;
use App\Helpers\Journal\JournalHelpers;
use App\Helpers\Colors;
use App\Helpers\Journal\JournalInterval;
use App\Model\Config\Config;
use App\Model\Config\ConfigCategory;
use App\Model\Config\ConfigNames;
use App\Model\Property\SelectedProperty;
use App\Model\Config\ConversionRateConfig;
use App\Model\Snapshots\SnapshotHelpers;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Log;
use Remp\Journal\AggregateRequest;
use Remp\Journal\ConcurrentsRequest;
use Remp\Journal\JournalContract;
use Remp\Journal\JournalException;
use Remp\Journal\ListRequest;

class DashboardController extends Controller
{
    private const NUMBER_OF_ARTICLES = 30;

    private $journal;

    private $journalHelper;

    private $snapshotHelpers;

    private $conversionRateConfig;

    private $selectedProperty;

    public function __construct(
        JournalContract $journal,
        SnapshotHelpers $snapshotHelpers,
        SelectedProperty $selectedProperty,
        ConversionRateConfig $conversionRateConfig
    ) {
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
        $this->snapshotHelpers = $snapshotHelpers;
        $this->selectedProperty = $selectedProperty;
        $this->conversionRateConfig = $conversionRateConfig;
    }

    public function index()
    {
        return $this->dashboardView('dashboard.index');
    }

    public function public()
    {
        return $this->dashboardView('dashboard.public');
    }

    private function dashboardView($template)
    {
        $data = [
            'options' => $this->options(),
            'conversionRateMultiplier' => $this->conversionRateConfig->getMultiplier(),
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

        $from = $journalInterval->timeAfter->tz('UTC');
        $to = $journalInterval->timeBefore->tz('UTC');

        $toNextDayStart = (clone $to)->tz($tz)->addDay()->startOfDay()->tz('UTC');
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
                $diff = $shadowFrom->tz('utc')->diff($from->tz('utc'));
                $hourDifference = $diff->invert === 0 ? $diff->h : - $diff->h;

                $shadowInterval = clone $journalInterval;
                $shadowInterval->timeAfter = $shadowFrom;
                $shadowInterval->timeBefore = $shadowTo;

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
        $totalCounts = [];
        foreach ($tags as $tag) {
            $emptyValues[$tag] = 0;
            $totalCounts[$tag] = 0;
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
            $totalCounts[$tag] += $item->count;
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

        // Reorder tags by total counts
        arsort($totalCounts);
        $i = 0;
        $tagOrdering = [];
        foreach ($totalCounts as $tag => $count) {
            $tagOrdering[$tag] = $i;
            $i++;
        }
        $orderedTags = array_keys($tagOrdering);

        $jsonResponse = [
            'results' => array_values($results),
            'previousResults' => array_values($shadowResults),
            'previousResultsSummed' => array_values($shadowResultsSummed),
            'intervalMinutes' => $intervalMinutes,
            'tags' => $orderedTags,
            'colors' => Colors::refererMediumTagsToColors($orderedTags),
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

        $endOfDay = (clone $timeAfter)->tz($tz)->endOfDay()->tz('UTC');

        $journalRequest = new AggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($intervalText, '0h');
        $journalRequest->addGroup('derived_referer_medium');
        $currentRecords = collect($this->journal->count($journalRequest));

        // Get all tags
        $tags = [];
        foreach ($currentRecords as $record) {
            $tags[$this->getRefererMediumFromJournalRecord($record)] = true;
        }

        // Compute shadow values for today and 7-days intervals
        $shadowRecords = [];
        if ($interval !== '30days') {
            $numberOfAveragedWeeks = $settings['compareWith'] === 'average' ? 4 : 1;

            for ($i = 1; $i <= $numberOfAveragedWeeks; $i++) {
                $from = (clone $timeAfter)->subWeeks($i);
                $to = (clone $timeBefore)->subWeeks($i);

                // If there was a time shift, remember time needs to be adjusted by the timezone difference
                $diff = $from->tz('utc')->diff($timeAfter->tz('utc'));
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
            // smaller intervals do not create good approximation
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

        $jsonResponse = [
            'intervalMinutes' => $intervalMinutes,
            'results' => $results,
            'previousResults' => array_values($shadowResults),
            'previousResultsSummed' => array_values($shadowResultsSummed),
            'tags' => $tags,
            'colors' => Colors::refererMediumTagsToColors($tags),
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
        $journalRequest->setTimeHistogram($interval, '0h');
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

        $colors = Colors::generalTagsToColors(array_keys($tags), true);
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
            $req->addGroup('article_id');
            if ($filterFrontPage) {
                $req->addFilter('derived_referer_host_with_path', ...$frontpageReferers);
            }
        }, $timeBefore);

        $topPages = [];
        $i = 0;
        $totalConcurrents = 0;
        foreach ($records as $record) {
            $totalConcurrents += $record->count;

            if ($i >= self::NUMBER_OF_ARTICLES) {
                continue;
            }

            $obj = new \stdClass();
            $obj->count = $record->count;
            $obj->external_article_id = $record->tags->article_id;

            if (!$record->tags->article_id) {
                $obj->title = 'Landing page';
                $obj->landing_page = true;
            } else {
                $article = Article::where('external_id', $record->tags->article_id)->first();
                if (!$article) {
                    continue;
                }

                $obj->landing_page = false;
                $obj->title = $article->title;
                $obj->published_at = $article->published_at->toAtomString();
                $obj->conversions_count = $article->conversions->count();
                $obj->article = $article;
            }
            $topPages[] = $obj;
            $i++;
        }

        // Top articles without landing page(s)
        $topArticles = collect($topPages)->filter(function ($item) {
            return !empty($item->external_article_id);
        })->pluck('article');

        // Timespent is computed as average of timespent values 2 hours in the past
        $externalIdsToTimespent = $this->journalHelper->timespentForArticles(
            $topArticles,
            (clone $timeBefore)->subHours(2)
        );

        $externalIdsToUniqueUsersCount = $this->journalHelper->uniqueBrowsersCountForArticles($topArticles);
        // Check for A/B titles/images for last 5 minutes
        $externalIdsToAbTestFlags = $this->journalHelper->abTestFlagsForArticles($topArticles, Carbon::now()->subMinutes(5));

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
                    $item->conversion_rate = Article::computeConversionRate($item->conversions_count, $item->unique_browsers_count, $this->conversionRateConfig);
                }

                $item->has_title_test = $externalIdsToAbTestFlags[$item->external_article_id]->has_title_test ?? false;
                $item->has_image_test = $externalIdsToAbTestFlags[$item->external_article_id]->has_image_test ?? false;

                $item->url = route('articles.show', ['article' => $item->article->id]);
            }
        }

        return response()->json([
            'articles' => $topPages,
            'totalConcurrents' => $totalConcurrents,
        ]);
    }
}
