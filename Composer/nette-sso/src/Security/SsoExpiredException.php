<?php

namespace Remp\NetteSso\Security;

class SsoExpiredException extends \Exception
{
    public $redirect;
}