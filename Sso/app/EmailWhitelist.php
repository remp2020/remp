<?php

namespace App;

use Illuminate\Support\Str;

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
            if (Str::endsWith($email, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
