<?php

namespace Remp\BeamModule\Model\Config;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Remp\BeamModule\Http\Requests\AuthorSegmentsConfigurationRequest;
use Remp\BeamModule\Model\BaseModel;

class ConfigCategory extends BaseModel
{
    const CODE_DASHBOARD = 'dashboard';
    const CODE_AUTHOR_SEGMENTS = 'author-segments';
    const CODE_SECTION_SEGMENTS = 'section-segments';

    protected $fillable = [
        'code',
        'display_name',
    ];

    public function configs(): HasMany
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
