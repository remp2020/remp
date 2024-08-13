<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $primaryKey = 'iso_code';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'iso_code',
        'name',
    ];

    protected $casts = [
        'iso_code' => 'string',
        'name' => 'string',
    ];
}
