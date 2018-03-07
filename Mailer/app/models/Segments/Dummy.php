<?php
namespace Remp\MailerModule\Segment;

class Dummy implements ISegment
{
    const PROVIDER_ALIAS = 'dummy-segment';

    public function provider()
    {
        return [static::PROVIDER_ALIAS => $this];
    }

    public function list()
    {
        return [
            [
                'name' => 'Dummy segment',
                'provider' => static::PROVIDER_ALIAS,
                'code' => 'dummy-segment',
                'group' => 'dummy',
            ],
        ];
    }

    public function users($segment)
    {
        return [1,2];
    }
}
