<?php

namespace App\Console\Commands;

use App\Author;
use App\Segment;
use App\SegmentBrowser;
use App\SegmentGroup;
use App\SegmentUser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAuthorsSegments extends Command
{
    const TIMESPENT_IGNORE_THRESHOLD_SECS = 3600;

    const COMMAND = 'segments:create-author-segments';

    protected $signature = self::COMMAND;

    protected $description = "Generate authors' segments from aggregated pageviews and timespent data.";

    public function handle()
    {
        $this->createAuthorSegments();
        $this->recomputeUsersForAuthorSegments();
        $this->recomputeBrowsersForAuthorSegments();
    }

    private function createAuthorSegments()
    {
        $segmentGroup = SegmentGroup::where(['code' => SegmentGroup::CODE_AUTHORS_SEGMENTS])->first();

        Author::chunk(200, function ($authors) use ($segmentGroup) {
            foreach ($authors as $author) {
                Segment::updateOrCreate([
                    'code' => 'author-' . $author->id
                ], [
                    'name' => 'Author ' . $author->name,
                    'active' => true,
                    'segment_group_id' => $segmentGroup->id,
                ]);
            }
        });
    }

    private function recomputeUsersForAuthorSegments()
    {
        $authorUsers = $this->groupDataFor('user_id');

        SegmentUser::truncate();

        foreach ($authorUsers as $authorId => $users) {
            $segment = Segment::where(['code' => "author-$authorId" ])->first();
            $toInsert = collect($users)->map(function ($userId) use ($segment) {
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


    private function recomputeBrowsersForAuthorSegments()
    {
        $authorBrowsers = $this->groupDataFor('browser_id');

        SegmentBrowser::truncate();

        foreach ($authorBrowsers as $authorId => $browsers) {
            $segment = Segment::where(['code' => "author-$authorId" ])->first();
            $toInsert = collect($browsers)->map(function ($browserId) use ($segment) {
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

    private function aggregatedPageviewsFor($groupParameter)
    {
        $results = [];
        $queryItems = DB::table('article_aggregated_views')
            ->select(
                DB::raw("$groupParameter, sum(pageviews) as total_pageviews")
            )
            ->join('article_author', 'article_author.article_id', '=', 'article_aggregated_views.article_id')
            ->where('timespent', '<=', self::TIMESPENT_IGNORE_THRESHOLD_SECS)
            ->whereRaw("$groupParameter <> ''")
            ->groupBy($groupParameter)
            ->cursor();

        foreach ($queryItems as $item) {
            $results[$item->$groupParameter] = (int) $item->total_pageviews;
        }
        return $results;
    }

    private function groupDataFor($groupParameter)
    {
        $totalPageviews = $this->aggregatedPageviewsFor($groupParameter);

        $queryItems =  DB::table('article_aggregated_views')
            ->select(
                DB::raw("$groupParameter, author_id, sum(pageviews) as total_pageviews, avg(timespent) as average_timespent")
            )
            ->join('article_author', 'article_author.article_id', '=', 'article_aggregated_views.article_id')
            ->where('timespent', '<=', self::TIMESPENT_IGNORE_THRESHOLD_SECS)
            ->whereRaw("$groupParameter <> ''")
            ->groupBy([$groupParameter, 'author_id'])
            ->havingRaw('avg(timespent) >= ?', ['120'])
            ->cursor();

        $segments = [];

        foreach ($queryItems as $item) {
            $ratio = (int) $item->total_pageviews / (float) $totalPageviews[$item->$groupParameter];
            // TODO specify conditions according to expert criteria
            if ($ratio >= 0.25 && $item->total_pageviews >= 5) {
                if (!array_key_exists($item->author_id, $segments)) {
                    $segments[$item->author_id] = [];
                }
                $segments[$item->author_id][] = $item->$groupParameter;
            }
        }

        return $segments;
    }
}
