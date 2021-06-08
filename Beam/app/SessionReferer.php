<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Model\Aggregable;
use Illuminate\Database\Eloquent\Model;

class SessionReferer extends Model implements Aggregable
{
    use HasFactory;

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

    public function groupableFields(): array
    {
        return ['subscriber', 'medium', 'source'];
    }
}
