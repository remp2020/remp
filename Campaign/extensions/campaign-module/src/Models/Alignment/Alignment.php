<?php

namespace Remp\CampaignModule\Models\Alignment;

class Alignment
{
    public function __construct(
        public string $key,
        public string $name,
        public array $style
    ) {
    }
}
