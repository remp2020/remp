<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\ArticleAggregatedView;
use Remp\BeamModule\Mail\SectionSegmentsResult;
use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigNames;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentBrowser;
use Remp\BeamModule\Model\SegmentGroup;
use Remp\BeamModule\Model\SegmentUser;
use Carbon\Carbon;
use Remp\BeamModule\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDO;

class ComputeSectionSegments extends Command
{
    const COMMAND = 'segments:compute-section-segments';

    const ALL_CONFIGS = [
        ConfigNames::SECTION_SEGMENTS_MIN_RATIO,
        ConfigNames::SECTION_SEGMENTS_MIN_AVERAGE_TIMESPENT,
        ConfigNames::SECTION_SEGMENTS_MIN_VIEWS,
        ConfigNames::SECTION_SEGMENTS_DAYS_IN_PAST
    ];

    private $minViews;
    private $minAverageTimespent;
    private $minRatio;
    private $historyDays;
    private $dateThreshold;

    protected $signature = self::COMMAND . ' 
    {--email=} 
    {--history=}
    {--min_views=} 
    {--min_average_timespent=} 
    {--min_ratio=}';

    protected $description = "Generate sections' segments from aggregated pageviews and timespent data.";

    public function handle()
    {
        // Using Cursor on large number of results causing memory issues
        // https://github.com/laravel/framework/issues/14919
        DB::connection()->getPdo()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $this->line('');
        $this->line('<info>***** Computing section segments *****</info>');
        $this->line('');

        $email = $this->option('email');

        $historyDays = $this->option('history');
        if ($historyDays === null) {
            $historyDays = Config::loadByName(ConfigNames::SECTION_SEGMENTS_DAYS_IN_PAST);
        }
        if (!in_array($historyDays, [30,60,90])) {
            $this->output->writeln('<error>ERROR</error> Invalid --history value provided (allowed values are 30, 60, 90): ' . $historyDays);
            return 1;
        }

        $this->minViews = Config::loadByName(ConfigNames::SECTION_SEGMENTS_MIN_VIEWS);
        $this->minAverageTimespent = Config::loadByName(ConfigNames::SECTION_SEGMENTS_MIN_AVERAGE_TIMESPENT);
        $this->minRatio = Config::loadByName(ConfigNames::SECTION_SEGMENTS_MIN_RATIO);
        $this->historyDays = $historyDays;
        $this->dateThreshold = Carbon::today()->subDays($historyDays);

        if ($email) {
            // Only compute segment statistics
            $this->line('Generating sections segments statistics');
            $this->computeSectionSegments($email);
        } else {
            // Generate real segments
            $this->line('Generating sections segments');
            $this->recomputeBrowsersForSectionSegments();
            $this->recomputeUsersForSectionSegments();
            $deletedSegments = self::deleteEmptySegments();
            $this->line('Deleting empty section segments');
            if ($deletedSegments > 0) {
                $this->line("Deleted $deletedSegments segments");
            }
        }

        $this->line(' <info>OK!</info>');
        return 0;
    }

    /**
     * @param $email
     */
    private function computeSectionSegments($email)
    {
        $minimalViews = $this->option('min_views') ?? $this->minViews;
        $minimalAverageTimespent = $this->option('min_average_timespent') ?? $this->minAverageTimespent;
        $minimalRatio = $this->option('min_ratio') ?? $this->minRatio;

        $results = [];
        $fromDay = $this->dateThreshold->toDateString();
        // only 30, 60 and 90 are allowed values
        $columnDays = 'total_views_last_' . $this->historyDays .'_days';

        $this->line("running browsers query");

        $browsersSql = <<<SQL
    SELECT T.section_id, sections.name, count(*) AS browser_count 
    FROM
      (SELECT browser_id, section_id, sum(pageviews) AS section_browser_views, avg(timespent) AS average_timespent
      FROM article_aggregated_views C JOIN article_section A ON A.article_id = C.article_id
      WHERE timespent <= 3600
      AND date >= ?
      GROUP BY browser_id, section_id
      HAVING
      section_browser_views >= ? AND
      average_timespent >= ? AND
      section_browser_views/(SELECT $columnDays FROM views_per_browser_mv WHERE browser_id = C.browser_id) >= ?
      ) T 
    JOIN sections ON sections.id = T.section_id
    GROUP BY section_id 
    ORDER BY browser_count DESC
SQL;
        $resultsBrowsers = DB::select($browsersSql, [$fromDay, $minimalViews, $minimalAverageTimespent, $minimalRatio]);

        foreach ($resultsBrowsers as $item) {
            $obj = new \stdClass();
            $obj->name = $item->name;
            $obj->browser_count = $item->browser_count;
            $obj->user_count = 0;

            $results[$item->section_id] = $obj;
        }

        $this->line("running users query");

        $usersSql = <<<SQL
    SELECT T.section_id, sections.name, count(*) AS user_count 
    FROM
        (SELECT user_id, section_id, sum(pageviews) AS section_user_views, avg(timespent) AS average_timespent
        FROM article_aggregated_views C JOIN article_section A ON A.article_id = C.article_id
        WHERE timespent <= 3600
        AND user_id <> ''
        AND date >= ?
        GROUP BY user_id, section_id
        HAVING
        section_user_views >= ? AND
        average_timespent >= ? AND
        section_user_views/(SELECT $columnDays FROM views_per_user_mv WHERE user_id = C.user_id) >= ?
        ) T JOIN sections ON sections.id = T.section_id
    GROUP BY section_id ORDER BY user_count DESC
SQL;
        
        $resultsUsers = DB::select($usersSql, [$fromDay, $minimalViews, $minimalAverageTimespent, $minimalRatio]);

        foreach ($resultsUsers as $item) {
            if (!array_key_exists($item->section_id, $results)) {
                $obj = new \stdClass();
                $obj->name = $item->name;
                $obj->browser_count = 0;
                $obj->user_count = 0;
                $results[$item->section_id] = $obj;
            }

            $results[$item->section_id]->user_count = $item->user_count;
        }

        Mail::to($email)->send(
            new SectionSegmentsResult($results, $minimalViews, $minimalAverageTimespent, $minimalRatio, $this->historyDays)
        );
    }

    private function recomputeUsersForSectionSegments()
    {
        SegmentUser::whereHas('segment.segmentGroup', function ($q) {
            $q->where('code', '=', SegmentGroup::CODE_SECTIONS_SEGMENTS);
        })->delete();

        $sectionUsers = $this->groupDataFor('user_id');

        $this->line("Updating segments users");

        foreach ($sectionUsers as $sectionId => $users) {
            if (count($users) === 0) {
                continue;
            }
            $segment = $this->getOrCreateSectionSegment($sectionId);

            foreach (array_chunk($users, 100) as $usersChunk) {
                $toInsert = collect($usersChunk)->map(function ($userId) use ($segment) {
                    return [
                        'segment_id' => $segment->id,
                        'user_id' => $userId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                });
                SegmentUser::insert($toInsert->toArray());
            }
        }
    }

    private function recomputeBrowsersForSectionSegments()
    {
        SegmentBrowser::whereHas('segment.segmentGroup', function ($q) {
            $q->where('code', '=', SegmentGroup::CODE_SECTIONS_SEGMENTS);
        })->delete();

        $sectionBrowsers = $this->groupDataFor('browser_id');

        $this->line("Updating segments browsers");

        foreach ($sectionBrowsers as $sectionId => $browsers) {
            if (count($browsers) === 0) {
                continue;
            }

            $segment = $this->getOrCreateSectionSegment($sectionId);

            foreach (array_chunk($browsers, 100) as $browsersChunk) {
                $toInsert = collect($browsersChunk)->map(function ($browserId) use ($segment) {
                    return [
                        'segment_id' => $segment->id,
                        'browser_id' => $browserId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                });
                SegmentBrowser::insert($toInsert->toArray());
            }
        }
    }

    private function aggregatedPageviewsFor($groupParameter)
    {
        $results = [];

        // Not using model to save memory
        $queryItems = DB::table(ArticleAggregatedView::getTableName())
            ->select($groupParameter, DB::raw('sum(pageviews) as total_pageviews'))
            ->whereNotNull($groupParameter)
            ->where('date', '>=', $this->dateThreshold)
            ->groupBy($groupParameter)
            ->cursor();

        foreach ($queryItems as $item) {
            $results[$item->$groupParameter] = (int) $item->total_pageviews;
        }
        return $results;
    }

    private function groupDataFor($groupParameter)
    {
        $this->getOutput()->write("Computing total pageviews for parameter '$groupParameter': ");
        $totalPageviews = $this->aggregatedPageviewsFor($groupParameter);
        $this->line(count($totalPageviews));

        $segments = [];

        $processed = 0;
        $step = 500;

        $bar = $this->output->createProgressBar(count($totalPageviews));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:1s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage("Computing segment items for parameter '$groupParameter'");

        foreach (array_chunk($totalPageviews, 500, true) as $totalPageviewsChunk) {
            $forItems = array_map('strval', array_keys($totalPageviewsChunk));

            $queryItems =  DB::table(ArticleAggregatedView::getTableName())->select(
                $groupParameter,
                'section_id',
                DB::raw('sum(pageviews) as total_pageviews'),
                DB::raw('avg(timespent) as average_timespent')
            )
                ->join('article_section', 'article_section.article_id', '=', 'article_aggregated_views.article_id')
                ->whereNotNull($groupParameter)
                ->where('date', '>=', $this->dateThreshold)
                ->whereIn($groupParameter, $forItems)
                ->groupBy([$groupParameter, 'section_id'])
                ->havingRaw('avg(timespent) >= ?', [$this->minAverageTimespent])
                ->cursor();

            foreach ($queryItems as $item) {
                if ($totalPageviews[$item->$groupParameter] === 0) {
                    continue;
                }
                $ratio = (int) $item->total_pageviews / $totalPageviews[$item->$groupParameter];
                if ($ratio >= $this->minRatio && $item->total_pageviews >= $this->minViews) {
                    if (!array_key_exists($item->section_id, $segments)) {
                        $segments[$item->section_id] = [];
                    }
                    $segments[$item->section_id][] = $item->$groupParameter;
                }
            }

            $processed += $step;
            $bar->setProgress($processed);
        }

        $bar->finish();
        $this->line('');
        return $segments;
    }

    private function getOrCreateSectionSegment($sectionId)
    {
        $segmentGroup = SegmentGroup::where(['code' => SegmentGroup::CODE_SECTIONS_SEGMENTS])->first();
        $section = Section::find($sectionId);

        return Segment::updateOrCreate([
            'code' => 'section-' . ($section ? $section->id : $sectionId)
        ], [
            'name' => 'Section ' . ($section ? $section->name : $sectionId),
            'active' => true,
            'segment_group_id' => $segmentGroup->id,
        ]);
    }

    public static function deleteEmptySegments(): int
    {
        $first = DB::table(SegmentUser::getTableName())
            ->select('segment_id')
            ->groupBy('segment_id');

        $unionQuery = DB::table(SegmentBrowser::getTableName())
            ->select('segment_id')
            ->groupBy('segment_id')
            ->union($first);

        return Segment::leftJoinSub($unionQuery, 't', function ($join) {
            $join->on('segments.id', '=', 't.segment_id');
        })->whereNull('t.segment_id')
            ->where('segment_group_id', SegmentGroup::getByCode(SegmentGroup::CODE_SECTIONS_SEGMENTS)->id)
            ->delete();
    }
}
