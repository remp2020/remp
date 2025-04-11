<?php

namespace Remp\BeamModule\Model\Config;

class ConversionRateConfig
{
    private int $multiplier;

    private int $decimalNumbers;

    /**
     * @deprecated Use static method build() instead. This constructor will be marked as private in the next major release.
     */
    public function __construct()
    {
    }

    public static function build(): self
    {
        $config = new self();
        $config->multiplier = Config::loadByName(ConfigNames::CONVERSION_RATE_MULTIPLIER);
        $config->decimalNumbers = Config::loadByName(ConfigNames::CONVERSION_RATE_DECIMAL_NUMBERS);

        return $config;
    }

    /**
     * @deprecated Use static method build() instead. This method will be removed in the next major release.
     */
    public function load()
    {
        $loadedConfig = self::build();

        $this->multiplier = $loadedConfig->multiplier;
        $this->decimalNumbers = $loadedConfig->decimalNumbers;
    }

    public function getMultiplier(): int
    {
        if (!$this->multiplier) {
            $this->load();
        }
        return $this->multiplier;
    }

    public function getDecimalNumbers(): int
    {
        if (!$this->decimalNumbers) {
            $this->load();
        }
        return $this->decimalNumbers;
    }
}
