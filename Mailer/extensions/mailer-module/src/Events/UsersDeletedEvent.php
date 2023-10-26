<?php

namespace Remp\MailerModule\Events;

/**
 * Event receives list of already deleted user emails.
 */
class UsersDeletedEvent
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
