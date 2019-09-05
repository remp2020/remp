<?php

namespace App\Model\Config;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConfigCategory extends Model
{
    const CODE_DASHBOARD = 'dashboard';

    protected $fillable = [
        'code',
        'display_name',
    ];

    public function configs()
    {
        return $this->hasMany(Config::class);
    }
}
