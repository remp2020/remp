<?php

namespace Remp\MailerModule\Filters;


class YesNoFilter
{
    public function process($string)
    {
        return (boolean)$string ? 'Yes' : 'No';
    }
}
