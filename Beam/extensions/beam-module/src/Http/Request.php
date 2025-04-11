<?php

namespace Remp\BeamModule\Http;

class Request extends \Illuminate\Http\Request
{
    public function isSecure(): bool
    {
        $isSecure = parent::isSecure();
        if ($isSecure) {
            return true;
        }

        if ($this->server->get('HTTPS') == 'on') {
            return true;
        }
        if ($this->server->get('HTTP_X_FORWARDED_PROTO') == 'https') {
            return true;
        }
        if ($this->server->get('HTTP_X_FORWARDED_SSL') == 'on') {
            return true;
        }

        return false;
    }
}
