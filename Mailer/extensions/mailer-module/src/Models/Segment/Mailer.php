<?php

namespace Remp\MailerModule\Models\Segment;

use Remp\MailerModule\Repositories\MailTypesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

class Mailer implements ISegment
{
    public const PROVIDER_ALIAS = 'mailer-segment';

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
        $segments = [];
        $mailTypes = $this->mailTypesRepository->all()->where(['deleted_at' => null]);
        foreach ($mailTypes as $mailType) {
            $segments[] = [
                'name' => 'Subscribers of ' . $mailType->title,
                'provider' => static::PROVIDER_ALIAS,
                'code' => 'subscribers-' . $mailType->code,
            ];
        }

        return $segments;
    }

    public function users(array $segment): array
    {
        $code = preg_replace('/^subscribers-/', '', $segment['code']);
        return $this->userSubscriptionsRepository->findSubscribedUserIdsByMailTypeCode($code);
    }
}
