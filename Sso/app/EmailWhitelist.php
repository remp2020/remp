<?php

namespace App;

class EmailWhitelist
{
    private $disabled = false;

    private $patterns = [];

    public function __construct()
    {
        switch ($list = config('jwt.domain_whitelist')) {
            case '*':
                $this->disabled = true;
                break;
            default:
                $this->patterns = explode(',', $list);
        }
    }

    public function validate($email)
    {
        if ($this->disabled) {
            return true;
        }
        foreach ($this->patterns as $pattern) {
            if (strpos($email, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}
