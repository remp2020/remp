<?php

namespace Remp\MailerModule\Helpers;

class BooleanHelper
{
    public function process($variable)
    {
        return (bool)$variable ? 'Yes' : 'No';
    }
}
