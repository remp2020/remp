<?php

namespace App\Contracts\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Guard as GuardContract;

/**
 * Class Illuminate
 *
 * Purpose of this class is override usage of default guard set by application
 * and always use Web guard when using JWTAuth to get user info.
 *
 * @package App\Contracts\Providers
 */
class Illuminate extends \Tymon\JWTAuth\Providers\Auth\Illuminate
{
    protected $auth;

    public function __construct(GuardContract $auth)
    {
        parent::__construct($auth);
        $this->auth = Auth::guard('web');
    }
}
