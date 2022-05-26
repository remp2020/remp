<?php
declare(strict_types=1);

namespace Remp\MailerModule\Filters;

class YesNoFilter
{
    public function process(int $input): string
    {
        return (boolean) $input ? 'Yes' : 'No';
    }
}
