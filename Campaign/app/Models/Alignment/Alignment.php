<?php

namespace App\Models\Alignment;

class Alignment
{
    public $label;

    public $style;

    public function __construct(string $label, array $style)
    {
        $this->label = $label;
        $this->style = $style;
    }
}