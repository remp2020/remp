<?php

namespace App\Http\Controllers;

use App\Article;
use App\Helpers\Journal\JournalHelpers;
use App\Helpers\Colors;
use App\Helpers\Journal\JournalInterval;
use App\Model\Config;
use App\Model\DashboardConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Remp\Journal\AggregateRequest;
use Remp\Journal\ConcurrentsRequest;
use Remp\Journal\JournalContract;

class DashboardController extends Controller
{
    private const NUMBER_OF_ARTICLES = 30;

    private $journal;

    private $journalHelper;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
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
        $options = [];
        foreach (DashboardConfig::getValues() as $value) {
            $options[$value] = Config::loadByName($value);
        }

        return view($template, [
            'enableFrontpageFiltering' => config('dashboard.frontpage_referer') !== null,
            'options' => $options
        ]);
    }

    public function options()
    {
        $options = [];
        foreach (DashboardConfig::getValues() as $value) {
            $options[$value] = Config::loadByName($value);
        }
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
        $toEndOfDay = $to->tz($tz)->endOfDay()->addSecond()->tz('UTC');

        $intervalMinutes = $journalInterval->intervalMinutes;

        $timePoints = $this->timePoints($from, $to, $intervalMinutes, true);
        $currentData = $this->dataFor($timePoints);

        $tags = [];
        foreach ($currentData as $item) {
            $tags[$item->derived_referer_medium] = true;
        }

        // Compute shadow values for today and 7-days intervals
        $shadowRecords = [];
        if ($interval !== '30days') {
            $numberOfAveragedWeeks = $settings['compareWith'] === 'average' ? 4 : 1;

            for ($i = 1; $i <= $numberOfAveragedWeeks; $i++) {
                $shadowFrom = (clone $from)->subWeeks($i);
                $shadowTo = (clone $toEndOfDay)->subWeeks($i);

                // If there was a time shift, remember time needs to be adjusted by the timezone difference
                $diff = $shadowFrom->tz('utc')->diff($from->tz('utc'));
                $hourDifference = $diff->invert === 0 ? $diff->h : - $diff->h;

                $timePoints = $this->timePoints($shadowFrom, $shadowTo, $intervalMinutes);
                foreach ($this->dataFor($timePoints) as $item) {
                    // we want to plot previous results on same points as current ones,
                    // therefore add week which was subtracted when data was queried
                    $correctedDate = Carbon::parse($item->time)
                        ->addWeeks($i)
                        ->addHours($hourDifference)
                        ->toIso8601ZuluString();

                    $currentTag = $item->derived_referer_medium;
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
        }

        // Get tags
        $tags = array_keys($tags);
        $emptyValues = [];
        foreach ($tags as $tag) {
            $emptyValues[$tag] = 0;
        }

        // Start building results
        $results = [];
        $shadowResults = [];
        $shadowResultsSummed = [];

        // Save current results
        foreach ($currentData as $item) {
            $zuluTime = Carbon::parse($item->time)->toIso8601ZuluString();
            if (!array_key_exists($zuluTime, $results)) {
                $results[$zuluTime] = array_merge($emptyValues, ['Date' => $zuluTime]);
            }
            $results[$zuluTime][$item->derived_referer_medium] = $item->count;
        }

        // Fill empty records for shadow values first
        if (count($shadowRecords) > 0) {
            $timeIterator = JournalHelpers::getTimeIterator($from, $intervalMinutes);
            while ($timeIterator->lessThan($toEndOfDay)) {
                $zuluDate = $timeIterator->toIso8601ZuluString();

                $shadowResults[$zuluDate] = $emptyValues;
                $shadowResults[$zuluDate]['Date'] = $shadowResultsSummed[$zuluDate]['Date'] = $zuluDate;
                $shadowResultsSummed[$zuluDate]['value'] = 0;

                $timeIterator->addMinutes($intervalMinutes);
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

        return response()->json([
            'results' => array_values($results),
            'intervalMinutes' => $intervalMinutes,
            'previousResults' => array_values($shadowResults),
            'previousResultsSummed' => array_values($shadowResultsSummed),
            'tags' => $tags,
            'colors' => Colors::refererMediumTagsToColors($tags)
        ]);
    }

    private function dataFor(array $timePoints)
    {
        return DB::table('article_views_snapshots')
            ->select('article_views_snapshots.time', 'derived_referer_medium', DB::raw('sum(count) as count'))
            ->whereIn('time', $timePoints)
            ->groupBy(['article_views_snapshots.time', 'derived_referer_medium'])
            // TODO: add grouping by 'explicit_referer_medium'
            ->get();
    }

    private function timePoints(Carbon $from, Carbon $to, int $intervalMinutes, bool $addLastMinute = false): array
    {
        $timeRecords = DB::table('article_views_snapshots')
            ->select('time')
            ->whereBetween('time', [$from, $to])
            ->groupBy('time')
            ->get()
            ->map(function ($item) {
                return Carbon::parse($item->time);
            })->toArray();

        $timeIterator = clone $from;

        $points = [];
        $i = 0;

        // Computes lowest time point (present in DB) per each $intervalMinutes window, starting from $from
        $lastPoint = null;
        while ($timeIterator->lte($to)) {
            $upperLimit = (clone $timeIterator)->addMinutes($intervalMinutes - 1);
            $timeIteratorString = $timeIterator->toIso8601ZuluString();

            while ($i < count($timeRecords)) {
                if (array_key_exists($timeIteratorString, $points)) {
                    break;
                }

                if ($timeRecords[$i]->between($timeIterator, $upperLimit)) {
                    $points[$timeIteratorString] = $timeRecords[$i];
                    $lastPoint = $timeRecords[$i];
                }

                $i++;
            }

            $timeIterator->addMinutes($intervalMinutes);
        }

        if ($addLastMinute && $lastPoint) {
            if ($timeRecords[count($timeRecords) - 1]->gt($lastPoint)) {
                $points[] = $timeRecords[count($timeRecords) - 1];
            }
        }

        return array_values($points);
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

        $journalRequest = new AggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($intervalText, '0h');
        $journalRequest->addGroup('derived_referer_medium');
        $currentRecords = collect($this->journal->count($journalRequest));

        // Get all tags
        $tags = [];
        foreach ($currentRecords as $records) {
            $tags[$records->tags->derived_referer_medium] = true;
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

                foreach ($this->pageviewRecordsBasedOnRefererMedium($from, $to, $intervalText) as $records) {
                    $currentTag = $records->tags->derived_referer_medium;
                    // update tags
                    $tags[$currentTag] = true;

                    if (!isset($records->time_histogram)) {
                        continue;
                    }

                    foreach ($records->time_histogram as $timeValue) {
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
        foreach ($currentRecords as $records) {
            if (!isset($records->time_histogram)) {
                continue;
            }
            $currentTag = $records->tags->derived_referer_medium;

            foreach ($records->time_histogram as $timeValue) {
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

        return response()->json([
            'intervalMinutes' => $intervalMinutes,
            'results' => $results,
            'previousResults' => array_values($shadowResults),
            'previousResultsSummed' => array_values($shadowResultsSummed),
            'tags' => $tags,
            'colors' => Colors::refererMediumTagsToColors($tags)
        ]);
    }

    private function pageviewRecordsBasedOnRefererMedium(Carbon $timeAfter, Carbon $timeBefore, string $interval)
    {
        $journalRequest = new AggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($interval, '0h');
        $journalRequest->addGroup('derived_referer_medium');
        return collect($this->journal->count($journalRequest));
    }

    public function mostReadArticles(Request $request)
    {
        $request->validate([
            'settings.onlyTrafficFromFrontPage' => 'required|boolean'
        ]);

        $settings = $request->get('settings');

        $timeBefore = Carbon::now();
        $timeAfter = (clone $timeBefore)->subSeconds(600); // Last 10 minutes

        $concurrentsRequest = new ConcurrentsRequest();

        $frontpageReferer = config('dashboard.frontpage_referer');
        if ($frontpageReferer && $settings['onlyTrafficFromFrontPage']) {
            $concurrentsRequest->addFilter('derived_referer_host_with_path', $frontpageReferer);
        }
        $concurrentsRequest->setTimeAfter($timeAfter);
        $concurrentsRequest->setTimeBefore($timeBefore);
        $concurrentsRequest->addGroup('article_id');

        // records are already sorted
        $records = collect($this->journal->concurrents($concurrentsRequest));

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
            (clone $timeAfter)->subHours(2)
        );

        $externalIdsToUniqueUsersCount = $this->journalHelper->uniqueUsersCountForArticles($topArticles);
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
                    // Artificially increased 10000x so conversion rate is more readable
                    $item->conversion_rate = number_format(($item->conversions_count / $item->unique_browsers_count) * 10000, 2);
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
