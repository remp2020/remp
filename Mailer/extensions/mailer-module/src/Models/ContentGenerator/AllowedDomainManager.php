<?php

namespace Remp\MailerModule\Models\ContentGenerator;

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
            if (str_ends_with($domain, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }
}
