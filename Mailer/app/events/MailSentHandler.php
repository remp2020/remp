<?php

namespace Remp\MailerModule\Events;

use League\Event\AbstractListener;
use League\Event\EventInterface;
use Nette\Utils\DateTime;
use Psr\Log\LoggerAwareTrait;
use Remp\MailerModule\Tracker\EventOptions;
use Remp\MailerModule\Tracker\ITracker;
use Remp\MailerModule\Tracker\User;
use Tracy\Debugger;

class MailSentHandler extends AbstractListener
{
    use LoggerAwareTrait;

    private $tracker;

    public function __construct(ITracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function handle(EventInterface $event)
    {
        if (!($event instanceof MailSentEvent)) {
            Debugger::log('invalid instance provided for MailSentHandler: ' . get_class($event));
            return false;
        }

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
