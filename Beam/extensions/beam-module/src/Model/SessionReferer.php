<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\SessionRefererFactory;
use Remp\BeamModule\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Model\Aggregable;

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

    protected static function newFactory(): SessionRefererFactory
    {
        return SessionRefererFactory::new();
    }

    public function aggregatedFields(): array
    {
        return ['count'];
    }

    public function groupableFields(): array
    {
        return ['subscriber', 'medium', 'source'];
    }
}
