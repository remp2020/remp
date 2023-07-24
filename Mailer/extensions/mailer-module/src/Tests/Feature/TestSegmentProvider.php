<?php
declare(strict_types=1);

namespace Tests\Feature;

use Remp\MailerModule\Models\Segment\ISegment;

class TestSegmentProvider implements ISegment
{
    public const PROVIDER_ALIAS = 'test-segment';

    public array $testUsers = [];

    public function provider(): string
    {
        return static::PROVIDER_ALIAS;
    }

    public function list(): array
    {
        return [
        ];
    }

    public function users(array $segment): array
    {
        return $this->testUsers;
    }
}
