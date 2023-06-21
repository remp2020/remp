<?php

namespace Remp\BeamModule\Model\Config;

class ConversionRateConfig
{
    private $multiplier;

    private $decimalNumbers;

    public function load()
    {
        $this->multiplier = Config::loadByName(ConfigNames::CONVERSION_RATE_MULTIPLIER);
        $this->decimalNumbers = Config::loadByName(ConfigNames::CONVERSION_RATE_DECIMAL_NUMBERS);
    }

    public function getMultiplier()
    {
        if (!$this->multiplier) {
            $this->load();
        }
        return $this->multiplier;
    }

    public function getDecimalNumbers()
    {
        if (!$this->decimalNumbers) {
            $this->load();
        }
        return $this->decimalNumbers;
    }
}
