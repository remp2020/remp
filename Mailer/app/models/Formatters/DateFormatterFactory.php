<?php

namespace Remp\MailerModule\Formatters;

use IntlDateFormatter;

class DateFormatterFactory
{
    public function getInstance($datetype, $timetype)
    {
        return new IntlDateFormatter(
            getenv('LOCALE'),
            $datetype,
            $timetype,
            getenv('TIMEZONE')
        );
    }
}
