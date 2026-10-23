<?php

namespace Remp\CampaignModule\Models\Schedule;

use DateTimeImmutable;
use DateTimeInterface;
use Remp\CampaignModule\Schedule;

/**
 * Schedule as it's cached, usable without the Eloquent model.
 */
readonly class ScheduleView
{
    public function __construct(
        private string $status,
        private ?DateTimeInterface $startTime,
        private ?DateTimeInterface $endTime,
    ) {
    }

    public static function fromModel(Schedule $schedule): self
    {
        return new self($schedule->status, $schedule->start_time, $schedule->end_time);
    }

    public static function fromCache(array $schedule): self
    {
        return new self(
            $schedule['status'],
            new DateTimeImmutable($schedule['start_time']),
            $schedule['end_time'] === null ? null : new DateTimeImmutable($schedule['end_time']),
        );
    }

    public function toCache(): array
    {
        return [
            'status' => $this->status,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime?->format('Y-m-d H:i:s'),
        ];
    }

    public function isRunning(): bool
    {
        return ScheduleState::isRunning($this->status, $this->startTime, $this->endTime);
    }
}
