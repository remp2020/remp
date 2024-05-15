<?php

namespace Remp\MailerModule\Models;

interface DataRetentionInterface
{
    public function removeData(): ?int;
}
