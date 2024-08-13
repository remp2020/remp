<?php

namespace Remp\CampaignModule\Models\Dimension;

class Dimensions
{
    public $key;

    public $name;

    public $width;

    public $height;

    public function __construct(string $key, string $name, $width, $height)
    {
        $this->key = $key;
        $this->name = $name;
        $this->width = $width;
        $this->height = $height;
    }
}
