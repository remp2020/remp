<?php

namespace Remp\CampaignModule\Models\Position;

class Position
{
    public function __construct(
        public string $key,
        public string $name,
        public array $style
    ) {
        foreach ($this->style as $pos => $val) {
            $this->style[$pos] = intval($val);
        }
    }
}
