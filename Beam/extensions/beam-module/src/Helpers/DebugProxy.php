<?php

namespace Remp\BeamModule\Helpers;

class DebugProxy
{
    private $obj;

    private $isDebug;

    /**
     * DebugProxy wraps an object and performs any function call on underlying object in case $isDebug === true
     * In case of false, no function call is made
     * This can be useful for example for output tools you do not want in production, such as ProgressBar
     */
    public function __construct($obj, bool $isDebug)
    {
        $this->obj = $obj;
        $this->isDebug = $isDebug;
    }

    public function __call($name, $arguments)
    {
        if ($this->isDebug) {
            call_user_func_array([$this->obj, $name], $arguments);
        }
    }
}
