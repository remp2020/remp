<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    const STATUS_READY = 'ready';
    const STATUS_RUNNING = 'running';
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
        $this->attributes['start_time'] = new Carbon($value, $this->timez);
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = new Carbon($value);
    }

    public function isRunning()
    {
        if ($this->status === self::STATUS_RUNNING && $this->end_time > Carbon::now()) {
            return true;
        }
        if ($this->start_time < Carbon::now() &&
            $this->end_time > Carbon::now() &&
            !in_array($this->status, [self::STATUS_PAUSED, self::STATUS_STOPPED])
        ) {
            return true;
        }
        return false;
    }

    public function isRunnable()
    {
        if ($this->end_time < Carbon::now()) {
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

    public function isStopped()
    {
        return $this->status === self::STATUS_STOPPED;
    }
}
