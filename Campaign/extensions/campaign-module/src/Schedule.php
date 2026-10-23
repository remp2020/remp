<?php

namespace Remp\CampaignModule;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\CampaignModule\Models\Schedule\ScheduleState;

class Schedule extends Model
{
    const STATUS_READY = 'ready';
    const STATUS_EXECUTED = 'executed';
    const STATUS_PAUSED = 'paused';
    const STATUS_STOPPED = 'stopped';

    public $fillable = [
        'campaign_id',
        'start_time',
        'end_time',
    ];

    public $attributes = [
        'status' => self::STATUS_READY,
    ];

    public $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public $dates = [
        'start_time',
        'end_time',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function setStartTimeAttribute($value)
    {
        if (!$value) {
            return;
        }
        $this->attributes['start_time'] = new Carbon($value);
    }

    public function setEndTimeAttribute($value)
    {
        if (!$value) {
            $this->attributes['end_time'] = null;
            return;
        }
        $this->attributes['end_time'] = new Carbon($value);
    }

    public function isRunning()
    {
        return ScheduleState::isRunning($this->status, $this->start_time, $this->end_time);
    }

    public function isRunnable()
    {
        if (!$this->endsInFuture()) {
            return false;
        }
        if ($this->status === self::STATUS_READY && $this->start_time > Carbon::now()) {
            return true;
        }
        if ($this->status === self::STATUS_PAUSED) {
            return true;
        }
        return false;
    }

    public function isEditable()
    {
        if ($this->start_time > Carbon::now() && $this->status === self::STATUS_READY) {
            return true;
        }
        return false;
    }

    public function isStopped()
    {
        return $this->status === self::STATUS_STOPPED || ($this->end_time !== null && $this->end_time < Carbon::now());
    }

    public function isPaused()
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function endsInFuture()
    {
        return ScheduleState::endsInFuture($this->end_time);
    }

    /** Scopes */

    public function scopeRunning(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [self::STATUS_READY, self::STATUS_EXECUTED])
            ->where('start_time', '<=', Carbon::now())
            ->where(
                function (Builder $endTimeQuery) {
                    $endTimeQuery
                        ->whereNull('end_time')
                        ->orWhere('end_time', '>=', Carbon::now());
                }
            );
    }

    public function scopeRunningOrPlanned(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [self::STATUS_READY, self::STATUS_EXECUTED])
            ->where(
                function (Builder $endTimeQuery) {
                    $endTimeQuery
                        ->whereNull('end_time')
                        ->orWhere('end_time', '>=', Carbon::now());
                }
            );
    }
}
