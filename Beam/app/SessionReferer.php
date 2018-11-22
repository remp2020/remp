<?php

namespace App;

use App\Model\Aggregable;
use Illuminate\Database\Eloquent\Model;

class SessionReferer extends Model implements Aggregable
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
        'count',
    ];

    public function aggregatedFields(): array
    {
        return ['count'];
    }
}
