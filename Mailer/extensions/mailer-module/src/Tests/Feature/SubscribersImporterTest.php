<?php
declare(strict_types=1);

namespace Tests\Feature;

use Remp\MailerModule\Models\SubscribersImporter;

class SubscribersImporterTest extends BaseFeatureTestCase
{
    private SubscribersImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = $this->inject(SubscribersImporter::class);
    }

    private function createExternalMailTypeWithVariant(bool $isMultiVariant = false): array
    {
        $mailType = $this->createMailTypeWithCategory(
            typeCode: 'external_test',
            typeName: 'External Test',
            isMultiVariant: $isMultiVariant,
            isExternal: true,
        );
        $variant = $this->createMailTypeVariant($mailType, 'Variant A', 'variant_a');
        return [$mailType, $variant];
    }

    private function getVariantSubscriberCount(int $variantId): int
    {
        return $this->userSubscriptionVariantsRepository->getTable()
            ->where('mail_type_variant_id', $variantId)
            ->where('mail_user_subscription.subscribed', true)
            ->count('*');
    }

    public function testImportNewSubscribers(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        $this->importer->import($mailType, [$variant], [
            'user1@example.com',
            'user2@example.com',
        ], false);

        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user1@example.com', $mailType->id));
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user2@example.com', $mailType->id));
        $this->assertEquals(2, $this->getVariantSubscriberCount($variant->id));
    }

    public function testImportAlreadySubscribed(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        $this->importer->import($mailType, [$variant], ['user1@example.com'], false);
        $this->importer->import($mailType, [$variant], ['user1@example.com'], false);

        // Should still be exactly 1 subscriber, not duplicated
        $this->assertEquals(1, $this->getVariantSubscriberCount($variant->id));
    }

    public function testImportSkipsUnsubscribedUsers(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        // Subscribe and then unsubscribe
        $this->userSubscriptionsRepository->subscribeUser($mailType, null, 'unsub@example.com', $variant->id, false);
        $this->userSubscriptionsRepository->unsubscribeEmail($mailType, 'unsub@example.com');

        $this->importer->import($mailType, [$variant], ['unsub@example.com'], false);

        // User should still be unsubscribed
        $this->assertFalse($this->userSubscriptionsRepository->isEmailSubscribed('unsub@example.com', $mailType->id));
        $this->assertEquals(0, $this->getVariantSubscriberCount($variant->id));
    }

    public function testImportAddsVariantToExistingSubscriber(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();
        $variantB = $this->createMailTypeVariant($mailType, 'Variant B', 'variant_b');

        // Subscribe to mail type with variant B
        $this->userSubscriptionsRepository->subscribeUser($mailType, null, 'user1@example.com', $variantB->id, false);

        // Import to variant A
        $this->importer->import($mailType, [$variant], ['user1@example.com'], false);

        $this->assertEquals(1, $this->getVariantSubscriberCount($variant->id));
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user1@example.com', $mailType->id));
    }

    public function testRemoveNotPresent(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        // Import two users
        $this->importer->import($mailType, [$variant], [
            'keep@example.com',
            'remove@example.com',
        ], false);
        $this->assertEquals(2, $this->getVariantSubscriberCount($variant->id));

        // Re-import with only one user, removing missing
        $this->importer->import($mailType, [$variant], ['keep@example.com'], true);

        $this->assertEquals(1, $this->getVariantSubscriberCount($variant->id));
        // Verify the correct one remains
        $subscription = $this->userSubscriptionsRepository->getTable()
            ->where(['user_email' => 'keep@example.com', 'mail_type_id' => $mailType->id])
            ->fetch();
        $this->assertTrue(
            $this->userSubscriptionVariantsRepository->variantSubscribed($subscription, $variant->id)
        );
    }

    public function testRemoveNotPresentDoesNotRemoveWhenDisabled(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        // Import two users
        $this->importer->import($mailType, [$variant], [
            'keep@example.com',
            'also_keep@example.com',
        ], false);

        // Re-import with only one user, but removal disabled
        $this->importer->import($mailType, [$variant], ['keep@example.com'], false);

        // Both should still be subscribed
        $this->assertEquals(2, $this->getVariantSubscriberCount($variant->id));
    }

    public function testEmptyImport(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        $this->importer->import($mailType, [$variant], [], false);

        $this->assertEquals(0, $this->getVariantSubscriberCount($variant->id));
    }

    public function testDeduplicatesEmails(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        $this->importer->import($mailType, [$variant], [
            'user1@example.com',
            'user1@example.com',
            'USER1@EXAMPLE.COM',
        ], false);

        $this->assertEquals(1, $this->getVariantSubscriberCount($variant->id));
    }

    public function testTrimsAndLowercasesEmails(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        $this->importer->import($mailType, [$variant], [
            '  User1@Example.COM  ',
        ], false);

        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user1@example.com', $mailType->id));
    }

    public function testFiltersInvalidEmails(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        $this->importer->import($mailType, [$variant], [
            'valid@example.com',
            'not-an-email',
            '',
        ], false);

        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('valid@example.com', $mailType->id));
        $this->assertFalse($this->userSubscriptionsRepository->isEmailSubscribed('not-an-email', $mailType->id));
        $this->assertEquals(1, $this->getVariantSubscriberCount($variant->id));
    }

    public function testImportWithoutVariant(): void
    {
        $mailType = $this->createMailTypeWithCategory(
            typeCode: 'external_no_variant',
            typeName: 'External No Variant',
            isExternal: true,
        );

        $this->importer->import($mailType, null, [
            'user1@example.com',
            'user2@example.com',
        ], false);

        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user1@example.com', $mailType->id));
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user2@example.com', $mailType->id));
    }

    public function testImportWithoutVariantRemoveNotPresent(): void
    {
        $mailType = $this->createMailTypeWithCategory(
            typeCode: 'external_no_variant_remove',
            typeName: 'External No Variant Remove',
            isExternal: true,
        );

        $this->importer->import($mailType, null, [
            'keep@example.com',
            'remove@example.com',
        ], false);
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('keep@example.com', $mailType->id));
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('remove@example.com', $mailType->id));

        $this->importer->import($mailType, null, ['keep@example.com'], true);

        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('keep@example.com', $mailType->id));
        $this->assertFalse($this->userSubscriptionsRepository->isEmailSubscribed('remove@example.com', $mailType->id));
    }

    public function testImportToMultipleVariants(): void
    {
        [$mailType, $variantA] = $this->createExternalMailTypeWithVariant(isMultiVariant: true);
        $variantB = $this->createMailTypeVariant($mailType, 'Variant B', 'variant_b');

        $this->importer->import($mailType, [$variantA, $variantB], [
            'user1@example.com',
            'user2@example.com',
        ], false);

        $this->assertEquals(2, $this->getVariantSubscriberCount($variantA->id));
        $this->assertEquals(2, $this->getVariantSubscriberCount($variantB->id));

        foreach (['user1@example.com', 'user2@example.com'] as $email) {
            $subscription = $this->userSubscriptionsRepository->getTable()
                ->where(['user_email' => $email, 'mail_type_id' => $mailType->id])
                ->fetch();
            $this->assertTrue($this->userSubscriptionVariantsRepository->variantSubscribed($subscription, $variantA->id));
            $this->assertTrue($this->userSubscriptionVariantsRepository->variantSubscribed($subscription, $variantB->id));
        }
    }

    public function testRemoveNotPresentWithMultipleVariants(): void
    {
        [$mailType, $variantA] = $this->createExternalMailTypeWithVariant(isMultiVariant: true);
        $variantB = $this->createMailTypeVariant($mailType, 'Variant B', 'variant_b');

        $this->importer->import($mailType, [$variantA, $variantB], [
            'keep@example.com',
            'remove@example.com',
        ], false);
        $this->assertEquals(2, $this->getVariantSubscriberCount($variantA->id));
        $this->assertEquals(2, $this->getVariantSubscriberCount($variantB->id));

        $this->importer->import($mailType, [$variantA, $variantB], ['keep@example.com'], true);

        $this->assertEquals(1, $this->getVariantSubscriberCount($variantA->id));
        $this->assertEquals(1, $this->getVariantSubscriberCount($variantB->id));

        $removedSubscription = $this->userSubscriptionsRepository->getTable()
            ->where(['user_email' => 'remove@example.com', 'mail_type_id' => $mailType->id])
            ->fetch();
        $this->assertFalse($this->userSubscriptionVariantsRepository->variantSubscribed($removedSubscription, $variantA->id));
        $this->assertFalse($this->userSubscriptionVariantsRepository->variantSubscribed($removedSubscription, $variantB->id));
    }

    public function testRemoveNotPresentOnlyAffectsSelectedVariants(): void
    {
        [$mailType, $variantA] = $this->createExternalMailTypeWithVariant(isMultiVariant: true);
        $variantB = $this->createMailTypeVariant($mailType, 'Variant B', 'variant_b');

        // user1 is subscribed to both variants
        $this->userSubscriptionsRepository->subscribeUser($mailType, null, 'user1@example.com', $variantA->id, false);
        $this->userSubscriptionsRepository->subscribeUser($mailType, null, 'user1@example.com', $variantB->id, false);

        // Import only 'user2' to variant A with removeNotPresent — should prune user1 from A but NOT touch B
        $this->importer->import($mailType, [$variantA], ['user2@example.com'], true);

        $user1Subscription = $this->userSubscriptionsRepository->getTable()
            ->where(['user_email' => 'user1@example.com', 'mail_type_id' => $mailType->id])
            ->fetch();
        $this->assertFalse($this->userSubscriptionVariantsRepository->variantSubscribed($user1Subscription, $variantA->id));
        $this->assertTrue($this->userSubscriptionVariantsRepository->variantSubscribed($user1Subscription, $variantB->id));

        $user2Subscription = $this->userSubscriptionsRepository->getTable()
            ->where(['user_email' => 'user2@example.com', 'mail_type_id' => $mailType->id])
            ->fetch();
        $this->assertTrue($this->userSubscriptionVariantsRepository->variantSubscribed($user2Subscription, $variantA->id));
    }

    public function testForceNoVariantSubscription(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        $this->importer->import($mailType, null, [
            'user1@example.com',
            'user2@example.com',
        ], false, true);

        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user1@example.com', $mailType->id));
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user2@example.com', $mailType->id));
        $this->assertEquals(0, $this->getVariantSubscriberCount($variant->id));
    }

    public function testForceNoVariantIgnoresProvidedVariants(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        // Passing variants together with forceNoVariant=true — force wins, no variant subscription should be created
        $this->importer->import($mailType, [$variant], ['user1@example.com'], false, true);

        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('user1@example.com', $mailType->id));
        $this->assertEquals(0, $this->getVariantSubscriberCount($variant->id));
    }

    public function testMixedScenario(): void
    {
        [$mailType, $variant] = $this->createExternalMailTypeWithVariant();

        // Pre-existing subscriber
        $this->userSubscriptionsRepository->subscribeUser($mailType, null, 'existing@example.com', $variant->id, false);

        // Pre-existing unsubscribed user
        $this->userSubscriptionsRepository->subscribeUser($mailType, null, 'unsub@example.com', $variant->id, false);
        $this->userSubscriptionsRepository->unsubscribeEmail($mailType, 'unsub@example.com');

        // Pre-existing subscriber that won't be in the import list (should be removed)
        $this->userSubscriptionsRepository->subscribeUser($mailType, null, 'old@example.com', $variant->id, false);

        $this->importer->import($mailType, [$variant], [
            'new@example.com',
            'existing@example.com',
            'unsub@example.com',
        ], true);

        // new@example.com: subscribed
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('new@example.com', $mailType->id));
        // existing@example.com: still subscribed
        $this->assertTrue($this->userSubscriptionsRepository->isEmailSubscribed('existing@example.com', $mailType->id));
        // unsub@example.com: still unsubscribed
        $this->assertFalse($this->userSubscriptionsRepository->isEmailSubscribed('unsub@example.com', $mailType->id));
        // old@example.com: removed from variant (but mail type subscription still active)
        $subscription = $this->userSubscriptionsRepository->getTable()
            ->where(['user_email' => 'old@example.com', 'mail_type_id' => $mailType->id])
            ->fetch();
        $this->assertFalse(
            $this->userSubscriptionVariantsRepository->variantSubscribed($subscription, $variant->id)
        );
    }
}
