<?php

namespace App\Http;

class Request extends \Illuminate\Http\Request
{
    public function isSecure()
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

    /**
     * Override Illuminate\Http\Request::ip() to get real client IP.
     *
     * @return string
     */
    public function ip()
    {
        $serverAll = parent::server();

        // source: https://stackoverflow.com/a/41769505
        foreach (['HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'] as $key){
            if (array_key_exists($key, $serverAll) === true){
                foreach (explode(',', $serverAll[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }

        return parent::ip();
    }
}
