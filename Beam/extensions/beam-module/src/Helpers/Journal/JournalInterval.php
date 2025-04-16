<?php

namespace Remp\BeamModule\Helpers\Journal;

use Remp\BeamModule\Model\Article;
use Carbon\Carbon;
use InvalidArgumentException;

class JournalInterval
{
    /**
     * Retention rules of article snapshots
     * Rules are used during compression of traffic data and also for rendering articles' traffic histogram
     * Rules define maximal detail of traffic for given window we can display
     * Array item describes [INTERVAL_START, INTERVAL_END, WINDOW_SIZE, WINDOW_SIZE_STRING], all in minutes
     */
    const RETENTION_RULES = [
        [0, 10, 1, '1m'], // in interval [0, 10) minutes, keep snapshot of article traffic every minute
        [10, 60, 5, '5m'], // in interval [10, 60) minutes, keep snapshot of article traffic max every 5 minutes
        [60, 60*24, 20, '20m'], // [60m, 1d)
        [60*24 , 60*24*8, 60, '1h'], // [1d, 8d)
        [60*24*7 , 60*24*30, 120, '2h'], // [7d, 30d)
        [60*24*30 , 60*24*90, 180, '3h'], // [30d, 90d)
        [60*24*90 , 60*24*180, 360, '6h'], // [90d, 180d)
        [60*24*180 , 60*24*365, 720, '12h'], // [180d, 1y)
        [60*24*365 , null, 1440, '24h'], // [1y, unlimited)
    ];

    public $timeAfter;
    public $timeBefore;
    public $intervalText;
    public $intervalMinutes;
    public $tz;
    public $cacheTTL;


    public function __construct(
        \DateTimeZone $tz,
        string $interval,
        Article $article = null,
        array $allowedIntervals = null,
        int $cacheTTL = null
    ) {
        if ($allowedIntervals && !in_array($interval, $allowedIntervals)) {
            throw new InvalidArgumentException("Parameter 'interval' must be one of the [" . implode(',', $allowedIntervals) . "] values, instead '$interval' provided");
        }

        $this->tz = $tz;

        switch ($interval) {
            case 'today':
                $this->timeAfter = Carbon::today($tz);
                $this->timeBefore = Carbon::now($tz);
                $this->intervalText = '20m';
                $this->intervalMinutes = 20;
                break;
            case '1day':
                $this->timeBefore = Carbon::now($tz);
                $this->timeAfter = $this->timeBefore->copy()->subDay();
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
            case 'first1day':
                if (!$article) {
                    throw new InvalidArgumentException("Missing article for 'first1day' option");
                }
                $this->timeAfter = (clone $article->published_at)->tz($tz);
                $this->timeBefore = $this->timeAfter->copy()->addDay();
                $this->intervalText = '20m';
                $this->intervalMinutes = 20;
                break;
            case 'first7days':
                if (!$article) {
                    throw new InvalidArgumentException("Missing article for 'first7days' option");
                }
                $this->timeAfter = (clone $article->published_at)->tz($tz);
                $this->timeBefore = $this->timeAfter->copy()->addDays(7);
                $this->intervalText = '1h';
                $this->intervalMinutes = 60;
                break;
            case 'first14days':
                if (!$article) {
                    throw new InvalidArgumentException("Missing article for 'first14days' option");
                }
                $this->timeAfter = (clone $article->published_at)->tz($tz);
                $this->timeBefore = $this->timeAfter->copy()->addDays(14);
                $this->intervalText = '1h';
                $this->intervalMinutes = 60;
                break;
            default:
                throw new InvalidArgumentException("Parameter 'interval' must be one of the [today,1day,7days,30days,all] values, instead '$interval' provided");
        }

        if (!$cacheTTL) {
            $this->cacheTTL = $this->intervalMinutes * 60;
        }
    }

    /**
     * @param \Remp\BeamModule\Model\Article $article
     *
     * @return array
     * @throws \Exception
     */
    private static function getIntervalDependingOnArticlePublishedDate(Article $article): array
    {
        $articleAgeInMins = $article->published_at->diffInMinutes(Carbon::now());

        foreach (self::RETENTION_RULES as $rule) {
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

    public function setIntervalMinutes($minutes): void
    {
        $this->intervalMinutes = $minutes;
        $this->intervalText = $minutes . 'm';
    }
}
