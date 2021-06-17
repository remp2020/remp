<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Sso;

class SsoExpiredException extends \Exception
{
    public $redirect;
}
