<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionReferer extends Model
{
    public $timestamps = false;

    protected $casts = [
        'subscriber' => 'boolean',
    ];

    protected $fillable = [
        'time_from',
        'time_to',
        'subscriber',
        'medium',
        'source',
    ];
}
