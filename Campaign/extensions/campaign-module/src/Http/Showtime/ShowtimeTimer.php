<?php

namespace Remp\CampaignModule\Http\Showtime;

/**
 * Measures duration of showtime's phases. Timings are reported only in responses to debug requests.
 */
class ShowtimeTimer
{
    /** @var array<string, float> duration of each phase in milliseconds */
    private array $durations = [];

    /** @var array<string, float> */
    private array $started = [];

    /** @var array<string, int> */
    private array $counts = [];

    private bool $reported = false;

    /**
     * @param float $requestStart Unix timestamp with microseconds, see $_SERVER['REQUEST_TIME_FLOAT'].
     */
    public function __construct(private float $requestStart)
    {
    }

    public function start(string $phase): void
    {
        $this->started[$phase] = microtime(true);
    }

    /**
     * Stopping the phase multiple times sums up all its durations.
     */
    public function stop(string $phase): void
    {
        $this->addDuration($phase, $this->started[$phase]);
    }

    /**
     * Records duration of the phase between $since and $until (Unix timestamps with microseconds, $until defaults to now).
     */
    public function addDuration(string $phase, float $since, ?float $until = null): void
    {
        $until ??= microtime(true);
        $this->durations[$phase] = ($this->durations[$phase] ?? 0) + ($until - $since) * 1000;
    }

    public function increment(string $counter, int $by = 1): void
    {
        $this->counts[$counter] = ($this->counts[$counter] ?? 0) + $by;
    }

    public function enableReport(): void
    {
        $this->reported = true;
    }

    public function isReported(): bool
    {
        return $this->reported;
    }

    public function report(): array
    {
        $durations = $this->durations;
        $durations['total'] = (microtime(true) - $this->requestStart) * 1000;

        return [
            'durations_ms' => array_map(static fn ($duration) => round($duration, 2), $durations),
            'counts' => $this->counts,
        ];
    }

    /**
     * @param array $report see report(); passed in, so the header and the response body report the same total
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Server-Timing
     */
    public static function serverTimingHeader(array $report): string
    {
        $metrics = [];
        foreach ($report['durations_ms'] as $phase => $duration) {
            $metrics[] = "{$phase};dur={$duration}";
        }
        return 'Server-Timing: ' . implode(', ', $metrics);
    }
}
