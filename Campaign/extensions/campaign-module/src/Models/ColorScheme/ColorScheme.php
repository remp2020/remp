<?php

namespace Remp\CampaignModule\Models\ColorScheme;

class ColorScheme
{
    public function __construct(
        public string $key,
        public string $label,
        public string $textColor,
        public string $backgroundColor,
        public string $buttonTextColor,
        public string $buttonBackgroundColor,
        public string $closeTextColor
    ) {
    }
}
