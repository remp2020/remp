<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Segment;

use Remp\MailerModule\Repositories\ActiveRow;

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
    public function list(): array;

    /**
     * Users returns array of user IDs matching the provided segment.
     *
     * @param array $segment
     * @return array
     */
    public function users(array $segment): array;
}
