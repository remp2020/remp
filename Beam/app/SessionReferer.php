<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Model\Aggregable;

class SessionReferer extends BaseModel implements Aggregable
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
