<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Config;

class SearchConfig
{
    private int $maxResultCount = 5;

    public function setMaxResultCount(?int $maxResultCount): void
    {
        if ($maxResultCount === null) {
            return;
        }

        $this->maxResultCount = $maxResultCount;
    }

    public function getMaxResultCount(): int
    {
        return $this->maxResultCount;
    }
}
