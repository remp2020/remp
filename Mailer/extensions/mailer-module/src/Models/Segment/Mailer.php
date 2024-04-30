<?php

namespace Remp\MailerModule\Models\Segment;

use Remp\MailerModule\Repositories\MailTypesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

class Mailer implements ISegment
{
    public const PROVIDER_ALIAS = 'mailer-segment';

    private const SEGMENT_EVERYONE = 'everyone';

    public function __construct(
        private MailTypesRepository $mailTypesRepository,
        private UserSubscriptionsRepository $userSubscriptionsRepository,
    ) {
    }

    public function provider(): string
    {
        return static::PROVIDER_ALIAS;
    }

    public function list(): array
    {
        $segments = [
            [
                'name' => 'Everyone',
                'provider' => static::PROVIDER_ALIAS,
                'code' => self::SEGMENT_EVERYONE,
            ],
        ];

        $mailTypes = $this->mailTypesRepository->all()->where(['deleted_at' => null]);
        foreach ($mailTypes as $mailType) {
            $segments[] = [
                'name' => 'Subscribers of ' . $mailType->title,
                'provider' => static::PROVIDER_ALIAS,
                'code' => $this->mailTypeSegment($mailType->code),
            ];
        }

        return $segments;
    }

    public function users(array $segment): array
    {
        if ($segment['code'] === self::SEGMENT_EVERYONE) {
            return $this->userSubscriptionsRepository->allSubscribers();
        }

        $code = preg_replace('/^subscribers-/', '', $segment['code']);
        return $this->userSubscriptionsRepository->findSubscribedUserIdsByMailTypeCode($code);
    }

    public static function mailTypeSegment(string $mailTypeCode): string
    {
        return 'subscribers-' . $mailTypeCode;
    }
}
