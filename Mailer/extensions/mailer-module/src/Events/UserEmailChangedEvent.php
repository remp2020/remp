<?php

namespace Remp\MailerModule\Events;

class UserEmailChangedEvent
{
    public function __construct(
        public readonly string $originalEmail,
        public readonly string $newEmail,
    ) {
    }
}
