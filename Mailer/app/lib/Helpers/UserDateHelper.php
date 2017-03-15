<?php

namespace Remp\MailerModule\Helpers;

use DateTime;

class UserDateHelper
{
    public function process($date)
    {
        if (!$date instanceof DateTime) {
            return (string) $date;
        };

        $format = 'j. M Y H:i:s';
        return $date->format($format);
    }
}
