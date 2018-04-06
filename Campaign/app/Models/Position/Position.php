<?php

namespace App\Models\Position;

class Position
{
    public $key;

    public $name;

    public function __construct(string $key, string $name)
    {
        $this->key = $key;
        $this->name = $name;
    }
}
