<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\SendingStats;

interface ISendingStatsFactory
{
    public function create(): SendingStats;
}
