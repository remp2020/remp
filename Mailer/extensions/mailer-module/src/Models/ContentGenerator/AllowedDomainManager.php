<?php

namespace Remp\MailerModule\Models\ContentGenerator;

use Nette\Utils\Strings;

class AllowedDomainManager
{
    private array $allowedDomains = [];

    public function addDomain(string $domain): void
    {
        $this->allowedDomains[] = $domain;
    }

    public function isAllowed(string $domain): bool
    {
        foreach ($this->allowedDomains as $allowedDomain) {
            if (Strings::endsWith($domain, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }
}
