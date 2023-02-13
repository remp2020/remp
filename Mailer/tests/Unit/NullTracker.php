<?php
declare(strict_types=1);

namespace Tests\Unit;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Tracker\EventOptions;
use Remp\MailerModule\Models\Tracker\ITracker;

class NullTracker implements ITracker
{
    public function trackEvent(DateTime $dateTime, string $category, string $action, EventOptions $options)
    {
    }
}
