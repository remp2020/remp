<?php
declare(strict_types=1);

namespace Remp\MailerModule\Events;

use Nette\Utils\DateTime;
use Psr\Log\LoggerAwareTrait;
use Remp\MailerModule\Models\Tracker\EventOptions;
use Remp\MailerModule\Models\Tracker\ITracker;
use Remp\MailerModule\Models\Tracker\User;

class MailSentEventHandler
{
    use LoggerAwareTrait;

    private $tracker;

    public function __construct(ITracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function __invoke(MailSentEvent $event)
    {
        $options = new EventOptions();
        $options->setUser(new User([
            'id' => $event->getUserId(),
        ]));
        $options->setFields([
            'email' => $event->getEmail(),
            'template_code' => $event->getTemplateCode(),
            'mail_job_batch_id' => $event->getBatchId(),
        ]);
        $this->tracker->trackEvent(
            DateTime::from($event->getTime()),
            'mail',
            'sent',
            $options
        );

        return true;
    }
}
