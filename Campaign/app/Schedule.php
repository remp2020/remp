<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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

    public function campaign()
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
            return;
        }
        $this->attributes['end_time'] = new Carbon($value);
    }

    public function isRunning()
    {
        if ($this->status === self::STATUS_EXECUTED && $this->endsInFuture()) {
            return true;
        }
        if ($this->start_time < Carbon::now() &&
            $this->endsInFuture() &&
            $this->status === self::STATUS_READY
        ) {
            return true;
        }
        return false;
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
        return $this->status === self::STATUS_STOPPED;
    }

    public function endsInFuture()
    {
        return !$this->end_time || $this->end_time > Carbon::now();
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
