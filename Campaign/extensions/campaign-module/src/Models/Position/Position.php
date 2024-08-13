<?php

namespace Remp\CampaignModule\Models\Position;

class Position
{
    public $key;

    public $name;

    public $style;

    public function __construct(string $key, string $name, array $style)
    {
        $this->key = $key;
        $this->name = $name;
        $this->style = $style;

        foreach ($this->style as $pos => $val) {
            $this->style[$pos] = intval($val);
        }
    }
}
