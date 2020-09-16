<?php

namespace App\Model\Config;

use App\Http\Request;
use App\Http\Requests\AuthorSegmentsConfigurationRequest;
use Illuminate\Database\Eloquent\Model;

class ConfigCategory extends Model
{
    const CODE_DASHBOARD = 'dashboard';
    const CODE_AUTHOR_SEGMENTS = 'author-segments';

    protected $fillable = [
        'code',
        'display_name',
    ];

    public function configs()
    {
        return $this->hasMany(Config::class);
    }

    public function getPairedRequestType(Request $request)
    {
        if ($this->code === self::CODE_AUTHOR_SEGMENTS) {
            return new AuthorSegmentsConfigurationRequest($request->all());
        }

        return $request;
    }

    public static function getSettingsTabUrl(string $configCategoryCode)
    {
        return route('settings.index') . '#' . $configCategoryCode;
    }
}
