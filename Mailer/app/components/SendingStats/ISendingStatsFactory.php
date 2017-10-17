<?php

namespace Remp\MailerModule\Components;

interface ISendingStatsFactory
{
    /** @return SendingStats */
    public function create();
}
