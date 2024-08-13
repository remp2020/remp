<?php

namespace Remp\CampaignModule\Http\Showtime;

class ShowtimeConfig
{
    public function __construct(
        private ?string $debugKey = null,
        private ?string $acceptLanguage = null,
        private bool $prioritizeBannerOnSamePosition = false,
        private bool $oneTimeBannerEnabled = true,
    ) {
    }

    public function setAcceptLanguage(string $language): self
    {
        $this->acceptLanguage = $language;
        return $this;
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

    public function getDebugKey(): ?string
    {
        return $this->debugKey;
    }

    public function setDebugKey(?string $debugKey): self
    {
        $this->debugKey = $debugKey;
        return $this;
    }
}
