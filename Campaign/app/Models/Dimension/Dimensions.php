<?php

namespace App\Models\Dimension;

class Dimensions
{
    public $label;

    public $width;

    public $height;

    public function __construct($label, $width, $height)
    {
        $this->label = $label;
        $this->width = $width;
        $this->height = $height;
    }
}