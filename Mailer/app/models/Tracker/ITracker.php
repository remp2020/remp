<?php

namespace Remp\MailerModule\Tracker;

interface ITracker
{
    /**
     * trackEvent tracks event with given metadata.
     *
     * @param \DateTime $dateTime
     * @param $category
     * @param $action
     * @param EventOptions $options
     * @return mixed
     */
    public function trackEvent(\DateTime $dateTime, $category, $action, EventOptions $options);
}
