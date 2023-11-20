<?php

namespace App\Http\Showtime;

class ShowtimeConfig
{
    private ?string $acceptLanguage = null;

    private bool $prioritizeBannerOnSamePosition = false;

    private bool $oneTimeBannerEnabled = true;

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

    public function setPrioritizeBannerOnSamePosition(bool $prioritizeBannerOnSamePosition): self
    {
        $this->prioritizeBannerOnSamePosition = $prioritizeBannerOnSamePosition;

        return $this;
    }

    public function isOneTimeBannerEnabled(): bool
    {
        return $this->oneTimeBannerEnabled;
    }

    public function setOneTimeBannerEnabled(bool $oneTimeBannerEnabled): self
    {
        $this->oneTimeBannerEnabled = $oneTimeBannerEnabled;

        return $this;
    }
}
