<?php

namespace Remp\MailerModule\Segment;

interface ISegment
{
    /**
     * Provider returns internal code for identifying the provider implementation.
     *
     * @return string
     */
    public function provider(): string;

    /**
     * List returns all available segments.
     *
     * @return array
     */
    public function list();

    /**
     * Users returns array of user IDs matching the provided segment.
     *
     * @param $segment
     * @return mixed
     */
    public function users($segment);
}
