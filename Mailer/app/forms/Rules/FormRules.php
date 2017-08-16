<?php

namespace Remp\MailerModule\Forms\Rules;

use Nette\Forms\IControl;

class FormRules
{
    const ADVANCED_EMAIL = 'Remp\MailerModule\Forms\Rules\FormRules::validateAdvancedEmail';

    public static function validateAdvancedEmail(IControl $control)
    {
        $email = $control->getValue();
        return false;
    }
}
