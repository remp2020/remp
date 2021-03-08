<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Formatters;

use IntlDateFormatter;

class DateFormatterFactory
{
    private $locale;
    private $timezone;

    public function __construct(string $locale, $timezone)
    {
        $this->locale = $locale;
        $this->timezone = $timezone;
    }

    public function getInstance(int $datetype, int $timetype): IntlDateFormatter
    {
        return new IntlDateFormatter(
            $this->locale,
            $datetype,
            $timetype,
            $this->timezone
        );
    }
}
