<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionDevice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'time_from',
        'time_to',
        'subscriber',
        'type',
        'model',
        'brand',
        'os_name',
        'os_version',
        'client_type',
        'client_name',
        'client_version',
    ];

    protected $casts = [
        'subscriber' => 'boolean',
    ];
}
