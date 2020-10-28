<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use Remp\MailerModule\Beam\UnreadArticlesResolver;
use Remp\MailerModule\Job\MailCache;
use Remp\MailerModule\Segment\Aggregator;
use Remp\MailerModule\User\IUser;
use Tests\Feature\BaseFeatureTestCase;

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
        $batch = $this->createBatch($template, $mailTypeVariant1);

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

    public function testFiltering()
    {
        // Prepare data
        $userList = $this->generateUsers(1000);

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        $layout = $this->createMailLayout();
        $mailType = $this->createMailTypeWithCategory();
        $template = $this->createTemplate($layout, $mailType);
        $batch = $this->createBatch($template);

        $numberOfSubscribedUsers = 50;

        for ($i = 1; $i <= $numberOfSubscribedUsers; $i++) {
            $item = $userList[$i];
            $this->userSubscriptionsRepository->subscribeUser($mailType, $item['id'], $item['email']);
        }

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->will($this->onConsecutiveCalls($userList, []));

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        // Push all user emails into queue
        $generator->insertUsersIntoJobQueue($batch, $userMap);

        $this->assertEquals(count($userList), $this->jobQueueRepository->totalCount());

        // Filter those that won't be sent
        $generator->filterQueue($batch);

        $this->assertEquals($numberOfSubscribedUsers, $this->jobQueueRepository->totalCount());
    }
}
