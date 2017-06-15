<?php

namespace Remp\MailerModule\Config;

class LocalConfig
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function value($key)
    {
        if ($this->exists($key)) {
            return $this->data[$key];
        }
        return false;
    }

    public function exists($key)
    {
        return isset($this->data[$key]);
    }
}
