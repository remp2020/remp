<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components;

interface IMissingConfigurationFactory
{
    /** @return MissingConfiguration */
    public function create(): MissingConfiguration;
}
