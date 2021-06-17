<?php

namespace Remp\MailerModule\Models\Traits;

use Nette\Utils\Strings;
use Remp\MailerModule\Repositories\InvalidSlugException;

trait SlugTrait
{
    public static function isValidSlug($value): bool
    {
        $slug = Strings::webalize($value);
        return $slug === $value;
    }

    public function assertSlug($value): void
    {
        if (!$this->isValidSlug($value)) {
            throw new InvalidSlugException("Provided string [{$value}] is not URL friendly slug.");
        }
    }
}
