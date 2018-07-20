<?php

namespace App\Console\Commands;

use App\Author;
use App\Segment;
use App\SegmentUser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAuthorsSegments extends Command
{
    const TIMESPENT_IGNORE_THRESHOLD_SECS = 3600;

    protected $signature = 'segments:create-author-segments';

    protected $description = "Generate authors' segments from aggregated pageviews and timespent data.";

    public function handle()
    {
        $this->createUserSegments();

        $queryItems = DB::table('article_user_views')
            ->select(
                DB::raw('user_id, author_id, sum(pageviews) as total_pageviews')
            )
            ->join('article_author', 'article_author.article_id', '=', 'article_user_views.article_id')
            ->where('timespent', '<=', self::TIMESPENT_IGNORE_THRESHOLD_SECS)
            ->groupBy(['user_id', 'author_id'])
            ->cursor();

        $userAuthorsPageviews = [];

        foreach ($queryItems as $item) {
            if (!array_key_exists($item->user_id, $userAuthorsPageviews)) {
                $userAuthorsPageviews[$item->user_id] = 0;
            }
            $userAuthorsPageviews[$item->user_id] += (int) $item->total_pageviews;
        }

        $queryItems2 = DB::table('article_user_views')
            ->select(
                DB::raw('user_id, author_id, sum(pageviews) as total_pageviews, avg(timespent) as average_timespent')
            )
            ->join('article_author', 'article_author.article_id', '=', 'article_user_views.article_id')
            ->where('timespent', '<=', self::TIMESPENT_IGNORE_THRESHOLD_SECS)
            ->groupBy(['user_id', 'author_id'])
            ->havingRaw('avg(timespent) >= ?', ['120'])
            ->cursor();

        $authorSegments = [];

        foreach ($queryItems2 as $item) {
            $ratio = (int) $item->total_pageviews / (float) $userAuthorsPageviews[$item->user_id];
            // TODO specify user segment conditions better
            if ($ratio >= 0.25 && $item->total_pageviews >= 5) {
                if (!array_key_exists($item->author_id, $authorSegments)) {
                    $authorSegments[$item->author_id] = [];
                }
                $authorSegments[$item->author_id][] = $item->user_id;
            }
        }

        // Remove existing segment users because we're going to recompute
        SegmentUser::truncate();

        foreach ($authorSegments as $authorId => $users) {
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

    private function createUserSegments()
    {
        Author::chunk(200, function ($authors) {
            foreach ($authors as $author) {
                Segment::updateOrCreate([
                    'code' => 'author-' . $author->id
                ], [
                    'name' => 'Author ' . $author->name,
                    'active' => true,
                    'type' => Segment::TYPE_EXPLICIT,
                    'public' => false
                ]);
            }
        });
    }
}
