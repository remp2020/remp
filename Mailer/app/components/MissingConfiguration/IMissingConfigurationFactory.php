<?php

namespace Remp\MailerModule\Components;

interface IMissingConfigurationFactory
{
    /** @return MissingConfiguration */
    public function create();
}
