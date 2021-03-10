<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms\Rules;

use Nette\Forms\Controls\BaseControl;
use Remp\MailerModule\Models\Traits\SlugTrait;

class StringValidator
{
    use SlugTrait;

    const SLUG = 'Remp\MailerModule\Forms\Rules\StringValidator::validateSlug';

    public static function validateSlug(BaseControl $input, $args): bool
    {
        return self::isValidSlug($input->getValue());
    }
}
