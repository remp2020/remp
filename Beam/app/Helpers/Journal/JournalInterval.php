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

    public function __construct(\DateTimeZone $tz, string $interval, Article $article = null, array $allowedIntervals = null)
    {
        if ($allowedIntervals && !in_array($interval, $allowedIntervals)) {
            throw new InvalidArgumentException("Parameter 'interval' must be one of the [" . implode(',', $allowedIntervals) . "] values, instead '$interval' provided");
        }

        switch ($interval) {
            case 'today':
                $this->timeAfter = Carbon::today($tz);
                $this->timeBefore = Carbon::now($tz);
                $this->intervalText = '20m';
                $this->intervalMinutes = 20;
                break;
            case '7days':
                $this->timeAfter = Carbon::today($tz)->subDays(6);
                $this->timeBefore = Carbon::now($tz);
                $this->intervalText = '1h';
                $this->intervalMinutes = 60;
                break;
            case '30days':
                $this->timeAfter = Carbon::today($tz)->subDays(29);
                $this->timeBefore = Carbon::now($tz);
                $this->intervalText = '2h';
                $this->intervalMinutes = 120;
                break;
            case 'all':
                if (!$article) {
                    throw new InvalidArgumentException("Missing article for 'all' option");
                }
                [$intervalText, $intervalMinutes] = self::getIntervalDependingOnArticlePublishedDate($article);
                $this->timeAfter = (clone $article->published_at)->tz($tz);
                $this->timeBefore = Carbon::now($tz);
                $this->intervalText = $intervalText;
                $this->intervalMinutes = $intervalMinutes;
                break;
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
