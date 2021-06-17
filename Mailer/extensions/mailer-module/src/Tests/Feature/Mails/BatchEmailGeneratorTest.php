<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use Remp\MailerModule\Models\Beam\UnreadArticlesResolver;
use Remp\MailerModule\Models\Job\MailCache;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Models\Users\IUser;
use Tests\Feature\BaseFeatureTestCase;
use Tests\Feature\TestUserProvider;

class BatchEmailGeneratorTest extends BaseFeatureTestCase
{
    private $mailCache;

    private $unreadArticlesGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailCache = $this->inject(MailCache::class);
        $this->unreadArticlesGenerator = $this->inject(UnreadArticlesResolver::class);
    }

    private function getGenerator($aggregator, $userProvider)
    {
        return new BatchEmailGeneratorWrapper(
            $this->jobsRepository,
            $this->jobQueueRepository,
            $this->batchesRepository,
            $aggregator,
            $userProvider,
            $this->mailCache,
            $this->unreadArticlesGenerator
        );
    }

    private function generateUsers(int $count): array
    {
        $userList = [];
        for ($i=1; $i<=$count; $i++) {
            $userList[$i] = [
                'id' => $i,
                'email' => "email{$i}@example.com"
            ];
        }
        return $userList;
    }

    public function testFilteringOtherVariants()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();
        $mailTypeVariant1 = $this->listVariantsRepository->add($mailType, 'variant1', 'v1', 100);
        $mailTypeVariant2 = $this->listVariantsRepository->add($mailType, 'variant2', 'v2', 100);

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $batch = $this->createJobAndBatch($template, $mailTypeVariant1);

        $userList = $this->generateUsers(4);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->will($this->onConsecutiveCalls($userList, []));

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        // Split user subscription into two variants
        foreach ($userList as $i => $item) {
            $this->userSubscriptionsRepository->subscribeUser(
                $mailType,
                $item['id'],
                $item['email'],
                $i % 2 === 0 ? $mailTypeVariant1->id : $mailTypeVariant2->id
            );
        }

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(2, $this->jobQueueRepository->totalCount());
    }

    public function testFiltering2()
    {
        // TUNE-UP test size (>=220)
        $allUsersCount = 200;
        $halfUsersCount = $allUsersCount / 2;

        // Prepare data
        $allUsersList = $this->generateUsers($allUsersCount);
        $userProvider = new TestUserProvider($allUsersList);
        $users1 = array_slice($allUsersList, 0, $halfUsersCount, true);
        $users2 = array_slice($allUsersList, $halfUsersCount, null, true);

        $aggregator1 = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($users1)
        ]);
        $aggregator2 = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($users2)
        ]);
        $layout = $this->createMailLayout();
        $mailType = $this->createMailTypeWithCategory();


        // Test simple job with 1 batch
        $template1 = $this->createTemplate($layout, $mailType);
        $batch1 = $this->createJobAndBatch($template1, null, 'context1');

        // Subscriber users
        $subscribedUsers1 = array_slice($users1, 0, 100);
        foreach ($subscribedUsers1 as $item) {
            $this->userSubscriptionsRepository->subscribeUser($mailType, $item['id'], $item['email']);
        }

        $generator1 = $this->getGenerator($aggregator1, $userProvider);
        $userMap1 = [];

        // Push all user emails into queue
        $generator1->insertUsersIntoJobQueue($batch1, $userMap1);
        $this->assertEquals(count($users1), $this->jobQueueCount($batch1, $template1));

        // Filter those that won't be sent
        $generator1->filterQueue($batch1);
        $this->assertEquals(count($subscribedUsers1), $this->jobQueueCount($batch1, $template1));


        ////////////
        // Now create another job with two batches
        ////////////
        $context2 = 'context2';

        $template2_1 = $this->createTemplate($layout, $mailType);
        $template2_2 = $this->createTemplate($layout, $mailType);

        $job2 = $this->createJob($context2);
        $batch2_1_maxEmailsCount = 20;
        $batch2_1 = $this->createBatch($job2, $template2_1, $batch2_1_maxEmailsCount);
        $batch2_2 = $this->createBatch($job2, $template2_2);

        $subscribedUsers2 = array_slice($users2, 0, 100);
        foreach ($subscribedUsers2 as $item) {
            $this->userSubscriptionsRepository->subscribeUser($mailType, $item['id'], $item['email']);
        }

        // Simulate some users have already received the same email (create mail_logs entries with same context)
        $alreadyReceivedEmailUsers2 = array_slice($users2, 0, 7);
        foreach ($alreadyReceivedEmailUsers2 as $item) {
            $this->mailLogsRepository->add(
                $item['email'],
                $template2_2->subject,
                $template2_2->id,
                $batch2_2->mail_job_id,
                $batch2_2->id,
                null,
                null,
                $context2
            );
        }

        $userMap2 = [];

        $generator2 = $this->getGenerator($aggregator2, $userProvider);
        // Process first batch
        $generator2->insertUsersIntoJobQueue($batch2_1, $userMap2);
        $generator2->filterQueue($batch2_1);
        $this->assertEquals($batch2_1_maxEmailsCount, $this->jobQueueCount($batch2_1, $template2_1));

        // Process second batch
        $generator2->insertUsersIntoJobQueue($batch2_2, $userMap2);
        $generator2->filterQueue($batch2_2);
        $batch2_2_expectedEmailsCount = count($subscribedUsers2) - $batch2_1_maxEmailsCount - count($alreadyReceivedEmailUsers2);
        $this->assertEquals($batch2_2_expectedEmailsCount, $this->jobQueueCount($batch2_2, $template2_2));
    }

    private function jobQueueCount($batch, $template): int
    {
        return $this->jobQueueRepository->getTable()->where([
            'mail_batch_id' => $batch->id,
            'mail_template_id' => $template->id,
        ])->count('*');
    }
}
