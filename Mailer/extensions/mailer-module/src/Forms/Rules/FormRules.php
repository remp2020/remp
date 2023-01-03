<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms\Rules;

use Nette\Forms\Control;
use Nette\SmartObject;

class FormRules
{
    use SmartObject;

    const ADVANCED_EMAIL = 'Remp\MailerModule\Forms\Rules\FormRules::validateAdvancedEmail';

    public static function validateAdvancedEmail(Control $control): bool
    {
        $value = $control->getValue();

        if (preg_match('#^(.+) +<(.*)>\z#', $value, $matches)) {
            $value = $matches[2];
        }

        $atom = "[-a-z0-9!#$%&'*+/=?^_`{|}~]"; // RFC 5322 unquoted characters in local-part
        $alpha = "a-z\x80-\xFF";                // superset of IDN
        return (bool)preg_match("(^
			(\"([ !#-[\\]-~]*|\\\\[ -~])+\"|$atom+(\\.$atom+)*)  # quoted or unquoted
			@
			([0-9$alpha]([-0-9$alpha]{0,61}[0-9$alpha])?\\.)+    # domain - RFC 1034
			[$alpha]([-0-9$alpha]{0,17}[$alpha])?                # top domain
		\\z)ix", $value);
    }
}
