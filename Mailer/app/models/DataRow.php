<?php

namespace Remp\MailerModule\Components\GeneratorWidgets\Widgets;

use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;

class DataRow implements \IteratorAggregate, IRow
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __set($column, $value)
    {
        throw new \Exception('Not supported');
    }

    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    public function &__get($key)
    {
        return $this->data[$key];
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        throw new \Exception('Not supported');
    }

    public function offsetGet($offset)
    {
        throw new \Exception('Not supported');
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception('Not supported');
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('Not supported');
    }

    public function setTable(Selection $name)
    {
        throw new \Exception('Not supported');
    }

    public function getTable()
    {
        throw new \Exception('Not supported');
    }

    public function getPrimary($throw = true)
    {
        throw new \Exception('Not supported');
    }

    public function getSignature($throw = true)
    {
        throw new \Exception('Not supported');
    }

    public function related($key, $throughColumn = null)
    {
        throw new \Exception('Not supported');
    }

    public function ref($key, $throughColumn = null)
    {
        throw new \Exception('Not supported');
    }
}
