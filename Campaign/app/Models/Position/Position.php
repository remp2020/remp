<?php

namespace App\Models\Position;

class Position
{
    public $label;

    public $style;

    public function __construct(string $label, array $style)
    {
        $this->label = $label;
        $this->style = $style;
    }
}