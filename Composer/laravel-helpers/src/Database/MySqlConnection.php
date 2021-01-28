<?php

namespace Remp\LaravelHelpers\Database;

use DateTimeInterface;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    /**
     * prepareBindings converts any DateTimeInterface objects to the default timezone.
     *
     * This conversion is necessary so the datetimes are correctly formatted as UTC dates before passed to the DB
     * for querying. Otherwise there would be a chance that locally formatted date would get to the query and the
     * wouldn't match the expectation.
     *
     * Application expects the whole communication with database to be in the UTC timezone. Both app timezone and
     * database timezone are hardcoded within the app config and not configurable.
     */
    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
        }

        return parent::prepareBindings($bindings);
    }
}
