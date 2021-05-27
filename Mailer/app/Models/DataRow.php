<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Exception;
use Nette\Database\Row;
use Nette\Database\Table\Selection;

class DataRow extends Row
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __set(string $column, $value): void
    {
        throw new Exception('Not supported');
    }

    public function __isset($key): bool
    {
        return isset($this->data[$key]);
    }

    public function &__get($key)
    {
        return $this->data[$key];
    }

    public function getIterator(): \RecursiveArrayIterator
    {
        return new \RecursiveArrayIterator($this->data);
    }

    public function offsetExists($key): bool
    {
        throw new Exception('Not supported');
    }

    public function offsetGet($key): bool
    {
        throw new Exception('Not supported');
    }

    public function offsetSet($key, $value): void
    {
        throw new Exception('Not supported');
    }

    public function offsetUnset($key): void
    {
        throw new Exception('Not supported');
    }

    public function setTable(Selection $name)
    {
        throw new Exception('Not supported');
    }

    public function getTable()
    {
        throw new Exception('Not supported');
    }

    public function getPrimary($throw = true)
    {
        throw new Exception('Not supported');
    }

    public function getSignature($throw = true)
    {
        throw new Exception('Not supported');
    }

    public function related($key, $throughColumn = null)
    {
        throw new Exception('Not supported');
    }

    public function ref($key, $throughColumn = null)
    {
        throw new Exception('Not supported');
    }
}
