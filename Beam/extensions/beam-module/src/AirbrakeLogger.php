<?php

namespace Remp\BeamModule;

class AirbrakeLogger extends \Kouz\LaravelAirbrake\AirbrakeLogger
{
    public function __invoke(array $config)
    {
        if (!config('airbrake.enabled')) {
            return new \Monolog\Logger('null', []);
        }
        return parent::__invoke($config);
    }
}
