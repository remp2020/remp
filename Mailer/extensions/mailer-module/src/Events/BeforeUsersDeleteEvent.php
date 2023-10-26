<?php

namespace Remp\MailerModule\Events;

/**
 * Event receives list of user emails to delete.
 * Event is emitted inside SQL transaction to ensure all the data is deleted at once.
 */
class BeforeUsersDeleteEvent
{
    private array $emails;

    public function __construct(array $emails)
    {
        $this->emails = $emails;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }
}
