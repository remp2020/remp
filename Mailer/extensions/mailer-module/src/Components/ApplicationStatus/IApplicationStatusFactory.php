<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\ApplicationStatus;

interface IApplicationStatusFactory
{
    /** @return ApplicationStatus */
    public function create(): ApplicationStatus;
}
