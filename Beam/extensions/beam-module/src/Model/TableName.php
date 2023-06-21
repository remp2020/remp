<?php

namespace Remp\BeamModule\Model;

trait TableName
{
    /**
     * Static function for getting table name.
     *
     * @return string
     */
    public static function getTableName()
    {
        $class = get_called_class();

        return (new $class())->getTable();
    }
}
