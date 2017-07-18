<?php

namespace Remp\LaravelSso\Contracts;

class SsoExpiredException extends \Exception
{
    public $redirect;
}