<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * asDateTime forces timezone of any date value within the model to be set in the default timezone.
     *
     * The reason why this method exists is that sometimes PHP ignores the requested timezone in the DateTime object
     * and uses the timezone provided in the RFC3339 datetime string. That caused following scenarios:
     *
     *   - Application would try to parse "2021-06-09T20:04:02+02:00".
     *   - Carbon instance would internally store it in the "+02:00" timezone, not the default timezone.
     *   - Laravel would format the date to "2021-06-09 20:04:02" and store it to the database.
     *
     * Database connection is forced to be in +00:00, so the database would accept it as "2021-06-09T20:04:02+00:00".
     *
     * Because of that, we need to make sure all the dates are in the default timezone before they're written
     * to the database.
     */
    protected function asDateTime($value)
    {
        $dateTime = parent::asDateTime($value);
        $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $dateTime;
    }
}
