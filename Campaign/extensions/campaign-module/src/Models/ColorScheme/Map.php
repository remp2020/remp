<?php

namespace Remp\CampaignModule\Models\ColorScheme;

use Illuminate\Support\Collection;

class Map
{
    const COLOR_SCHEMES_MAP_REDIS_KEY = 'color_schemes_map';

    /** @var ColorScheme[] */
    protected array $colorSchemes = [];

    public function __construct(array $colorSchemesConfig)
    {
        foreach ($colorSchemesConfig as $key => $dc) {
            $this->colorSchemes[$key] = new ColorScheme(
                $key,
                $dc['label'],
                $dc['textColor'],
                $dc['backgroundColor'],
                $dc['buttonTextColor'],
                $dc['buttonBackgroundColor'],
                $dc['closeTextColor'],
            );
        }
    }

    public function colorSchemes(): Collection
    {
        return collect($this->colorSchemes);
    }
}
