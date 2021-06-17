<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\MissingConfiguration;

interface IMissingConfigurationFactory
{
    /** @return MissingConfiguration */
    public function create(): MissingConfiguration;
}
