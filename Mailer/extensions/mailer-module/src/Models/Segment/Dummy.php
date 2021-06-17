<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Segment;

class Dummy implements ISegment
{
    const PROVIDER_ALIAS = 'dummy-segment';

    public function provider(): string
    {
        return static::PROVIDER_ALIAS;
    }

    public function list(): array
    {
        return [
            [
                'name' => 'Dummy segment',
                'provider' => static::PROVIDER_ALIAS,
                'code' => 'dummy-segment',
                'group' => [
                    'id' => 0,
                    'name' => 'dummy',
                    'sorting' => 1
                ]
            ],
        ];
    }

    public function users(array $segment): array
    {
        return [1,2];
    }
}
