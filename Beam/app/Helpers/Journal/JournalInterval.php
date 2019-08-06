<?php

namespace App\Helpers\Journal;

use App\Article;
use App\Console\Commands\CompressSnapshots;
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


    /**
     * @param Article $article
     *
     * @return array
     * @throws \Exception
     */
    private static function getIntervalDependingOnArticlePublishedDate(Article $article): array
    {
        $articleAgeInMins = Carbon::now()->diffInMinutes($article->published_at);

        foreach (CompressSnapshots::RETENTION_RULES as $rule) {
            $startMinute = $rule[0];
            $endMinute = $rule[1];
            $windowSizeInMinutes = $rule[2];
            $windowSizeText = $rule[3];


            if ($endMinute === null || $articleAgeInMins < $endMinute) {
                return [$windowSizeText, $windowSizeInMinutes];
            }
        }

        throw new \Exception("No fitting rule for article {$article->id}");
    }
}
