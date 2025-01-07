<?php

namespace Remp\CampaignModule\Models\Dimension;

class Dimensions
{
    public function __construct(
        public string $key,
        public string $name,
        public $width,
        public $height
    ) {
    }
}
