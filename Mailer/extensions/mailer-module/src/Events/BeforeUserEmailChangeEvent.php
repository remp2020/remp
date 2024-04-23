<?php

namespace Remp\MailerModule\Events;

class BeforeUserEmailChangeEvent
{
    public function __construct(
        public readonly string $originalEmail,
        public readonly string $newEmail,
    ) {
    }
}
