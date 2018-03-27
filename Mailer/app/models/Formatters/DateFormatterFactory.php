<?php

namespace Remp\MailerModule\Formatters;

use IntlDateFormatter;

class DateFormatterFactory
{
    private $locale;
    private $timezone;

    public function __construct($locale, $timezone)
    {
        $this->locale = $locale;
        $this->timezone = $timezone;
    }

    public function getInstance($datetype, $timetype)
    {
        return new IntlDateFormatter(
            $this->locale,
            $datetype,
            $timetype,
            $this->timezone
        );
    }
}
