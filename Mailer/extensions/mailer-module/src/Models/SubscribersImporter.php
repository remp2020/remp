<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;

readonly class SubscribersImporter
{
    public function __construct(
        private UserSubscriptionsRepository $userSubscriptionsRepository,
        private UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
    ) {
    }

    public function import(
        ActiveRow $mailType,
        ?array $variants,
        array $emails,
        bool $removeNotPresent,
        bool $forceNoVariant = false,
    ): int {
        $emails = array_map(static fn(string $line) => strtolower(trim($line)), $emails);
        $emails = array_filter($emails, static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $emails = array_unique($emails);

        $subscribedEmails = [];
        foreach (array_chunk($emails, 100) as $emailsChunk) {
            $unsubscribedEmails = $this->userSubscriptionsRepository->getTable()
                ->where('user_email IN (?)', $emailsChunk)
                ->where('mail_type_id', $mailType->id)
                ->where('subscribed', 0)
                ->fetchPairs('user_email', 'user_email');

            $emailsToSubscribe = array_diff($emailsChunk, array_values($unsubscribedEmails));
            foreach ($emailsToSubscribe as $email) {
                if ($forceNoVariant || empty($variants)) {
                    $this->userSubscriptionsRepository->subscribeUser(
                        mailType: $mailType,
                        userId: null,
                        email: $email,
                        forceNoVariantSubscription: $forceNoVariant,
                    );
                } else {
                    foreach ($variants as $variant) {
                        $this->userSubscriptionsRepository->subscribeUser(
                            mailType: $mailType,
                            userId: null,
                            email: $email,
                            variantId: $variant->id,
                        );
                    }
                }
                $subscribedEmails[] = $email;
            }
        }

        if ($removeNotPresent) {
            if (!empty($variants)) {
                $variantIds = [];
                foreach ($variants as $variant) {
                    $variantIds[] = $variant->id;
                }

                $subscribersToRemove = $this->userSubscriptionVariantsRepository->getTable()
                    ->select('mail_user_subscription_variants.*')
                    ->where('mail_type_variant_id IN (?)', $variantIds)
                    ->where('mail_user_subscription.subscribed', true);
                if ($subscribedEmails) {
                    $subscribersToRemove = $subscribersToRemove->where('mail_user_subscription.user_email NOT IN (?)', $subscribedEmails);
                }
                $subscribersToRemove = $subscribersToRemove->fetchAll();

                foreach ($subscribersToRemove as $subscriber) {
                    $this->userSubscriptionsRepository->unsubscribeUserVariant(
                        userSubscription: $subscriber->mail_user_subscription,
                        variant: $subscriber->mail_type_variant,
                        keepMailTypeSubscription: true,
                    );
                }
            } else {
                $subscribersToRemove = $this->userSubscriptionsRepository->getTable()
                    ->where([
                        'mail_type_id' => $mailType->id,
                        'subscribed' => true,
                    ]);
                if ($subscribedEmails) {
                    $subscribersToRemove = $subscribersToRemove->where('user_email NOT IN (?)', $subscribedEmails);
                }
                $subscribersToRemove = $subscribersToRemove->fetchAll();

                foreach ($subscribersToRemove as $subscriber) {
                    $this->userSubscriptionsRepository->unsubscribeEmail($mailType, $subscriber->user_email);
                }
            }
        }

        return count($subscribedEmails);
    }
}
