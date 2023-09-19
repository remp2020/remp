<?php

namespace App\Http\Showtime;

class ShowtimeConfig
{
    private ?string $acceptLanguage = null;

    private bool $prioritizeBannerOnSamePosition = false;

    public function setAcceptLanguage(string $language): void
    {
        $this->acceptLanguage = $language;
    }

    public function getAcceptLanguage(): ?string
    {
        return $this->acceptLanguage;
    }

    public function isPrioritizeBannerOnSamePosition(): bool
    {
        return $this->prioritizeBannerOnSamePosition;
    }

    public function setPrioritizeBannerOnSamePosition(bool $prioritizeBannerOnSamePosition): void
    {
        $this->prioritizeBannerOnSamePosition = $prioritizeBannerOnSamePosition;
    }
}
