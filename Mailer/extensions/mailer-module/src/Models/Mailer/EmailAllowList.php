<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Mailer;

class EmailAllowList
{
    private array $allowList = [];

    /**
     * @param string $allow
     */
    public function allow(string $allow): void
    {
        $this->allowList[] = $allow;
    }

    /**
     * @param string $isAllowed
     * @return bool
     */
    public function isAllowed(string $isAllowed): bool
    {
        if (empty($this->allowList)) {
            return true;
        }

        foreach ($this->allowList as $allowItem) {
            if (str_contains($isAllowed, $allowItem)) {
                return true;
            }
        }
        return false;
    }

    public function reset(): void
    {
        $this->allowList = [];
    }
}
