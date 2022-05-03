<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Config;

class LocalizationConfig
{
    private string $defaultLocale;

    private array $secondaryLocales = [];

    public function __construct(string $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function addSecondaryLocales(array $locales): void
    {
        $this->secondaryLocales = $locales;
    }

    public function getSecondaryLocales(): array
    {
        return $this->secondaryLocales;
    }

    public function getAvailableLocales(): array
    {
        return array_merge([$this->getDefaultLocale()], $this->getSecondaryLocales());
    }

    public function isTranslatable(string $locale = null): bool
    {
        if (!$locale) {
            return false;
        }

        return in_array($locale, $this->getSecondaryLocales(), true) && $locale !== $this->getDefaultLocale();
    }
}
