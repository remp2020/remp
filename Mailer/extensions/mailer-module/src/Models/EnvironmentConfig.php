<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

class EnvironmentConfig
{
    public function get(string $key): ?string
    {
        if (!isset($_ENV[$key])) {
            return null;
        }
        $val = $_ENV[$key];
        if ($val === false || $val === '') {
            return null;
        }
        return $val;
    }

    public function getInt(string $key): ?int
    {
        $val = $this->get($key);
        if ($val === null) {
            return $val;
        }
        return (int)$val;
    }

    public function getDsn(): string
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

    public function getBool(string $key): ?bool
    {
        $value = $this->get($key);
        if ($value === null) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
