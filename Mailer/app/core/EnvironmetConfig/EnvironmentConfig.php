<?php

namespace Remp\MailerModule;

class EnvironmentConfig
{
    public function get($key)
    {
        return getenv($key);
    }

    public function getDsn()
    {
        $port = $this->get('DB_PORT');
        if (!$port) {
            $port = 3306;
        }

        return $this->get('DB_ADAPTER') .
            ':host=' . $this->get('DB_HOST') .
            ';dbname=' . $this->get('DB_NAME') .
            ';port=' . $port;
    }
}
