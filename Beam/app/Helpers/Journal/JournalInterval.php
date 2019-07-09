<?php

namespace App\Helpers\Journal;

use App\Article;
use Carbon\Carbon;
use InvalidArgumentException;

class JournalInterval
{
    public $timeAfter;
    public $timeBefore;
    public $intervalText;
    public $intervalMinutes;

    public function __construct(Carbon $timeAfter, Carbon $timeBefore, string $intervalText, int $intervalMinutes)
    {
        $this->timeBefore = $timeBefore;
        $this->timeAfter = $timeAfter;
        $this->intervalText = $intervalText;
        $this->intervalMinutes = $intervalMinutes;
    }

    public static function for(\DateTimeZone $tz, string $interval, array $allowedIntervals = null): JournalInterval
    {
        return self::forArticle($tz, $interval, null, $allowedIntervals);
    }

    public static function forArticle(\DateTimeZone $tz, string $interval, Article $article = null, array $allowedIntervals = null): JournalInterval
    {
        if ($allowedIntervals && !in_array($interval, $allowedIntervals)) {
            throw new InvalidArgumentException("Parameter 'interval' must be one of the [" . implode(',', $allowedIntervals) . "] values, instead '$interval' provided");
        }

        switch ($interval) {
            case 'today':
                return new JournalInterval(
                    Carbon::today($tz),
                    Carbon::now($tz),
                    '20m',
                    20
                );
            case '7days':
                return new JournalInterval(
                    Carbon::today($tz)->subDays(6),
                    Carbon::now($tz),
                    '1h',
                    60
                );
            case '30days':
                return new JournalInterval(
                    Carbon::today($tz)->subDays(29),
                    Carbon::now($tz),
                    '2h',
                    120
                );
            case 'all':
                if (!$article) {
                    throw new InvalidArgumentException("Missing article for 'all' option");
                }
                [$intervalText, $intervalMinutes] = self::getIntervalDependingOnArticlePublishedDate($article);
                return new JournalInterval(
                    (clone $article->published_at)->tz($tz),
                    Carbon::now($tz),
                    $intervalText,
                    $intervalMinutes
                );
            default:
                throw new InvalidArgumentException("Parameter 'interval' must be one of the [today,7days,30days,all] values, instead '$interval' provided");
        }
    }

    private static function getIntervalDependingOnArticlePublishedDate(Article $article): array
    {
        $articleAgeInMins = Carbon::now()->diffInMinutes($article->published_at);

        if ($articleAgeInMins <= 60) { // 1 hour
            return ["5m", 5];
        }
        if ($articleAgeInMins <= 60*24) { // 1 day
            return ["20m", 20];
        }
        if ($articleAgeInMins <= 7*60*24) { // 7 days
            return ["1h", 60];
        }
        if ($articleAgeInMins <= 30*60*24) { // 30 days
            return ["2h", 120];
        }
        if ($articleAgeInMins <= 90*60*24) { // 90 days
            return ["3h", 180];
        }
        if ($articleAgeInMins <= 180*60*24) { // 180 days
            return ["6h", 360];
        }
        if ($articleAgeInMins <= 365*60*24) { // 1 year
            return ["12h", 720];
        }
        return ["24h", 1440]; // 1+ year
    }
}
