<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use Nette\Utils\DateTime;
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
            $this->jobQueueRepository,
            $aggregator,
            $userProvider,
            $this->mailCache,
            $this->unreadArticlesGenerator,
        );
    }

    private function generateUsers(int $count, int $startFromId = 1): array
    {
        $userList = [];
        for ($i=$startFromId; $i < $startFromId + $count; $i++) {
            $userList[$i] = [
                'id' => $i,
                'email' => "email{$i}@example.com"
            ];
        }
        return $userList;
    }

    public function testOnlyIncludeSegments()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $job = $this->createJob(includeSegments: [['code' => 's1', 'provider' => 'p'], ['code' => 's2', 'provider' => 'p']]);
        $batch = $this->createBatch($job, $template);

        $userList1 = $this->generateUsers(100);
        $userList2 = $this->generateUsers(50, 101);
        $userList = array_merge($userList1, $userList2);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, $ids);
            }

            return [];
        });

        $aggregator = $this->createMock(Aggregator::class);
        $map = [
            [['provider' => 'p', 'code' => 's1'], array_map(static fn($i) => $i['id'], $userList1)],
            [['provider' => 'p', 'code' => 's2'], array_map(static fn($i) => $i['id'], $userList2)]
        ];
        $aggregator->method('users')->willReturnMap($map);

        $this->subscribeUsers($mailType, $userList);

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(150, $this->jobQueueCount($batch, $template));

        $expectedUsers = $this->getUserEmailsByKeys($userList, array_keys($userList));
        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing($expectedUsers, $actualUsers);
    }

    public function testIntersectingSegments()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $job = $this->createJob(includeSegments: [['code' => 's1', 'provider' => 'p'], ['code' => 's2', 'provider' => 'p']]);
        $batch = $this->createBatch($job, $template);

        $userList1 = $this->generateUsers(100);
        $userList2 = $this->generateUsers(50, 20);
        $userList = array_merge($userList1, $userList2);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, $ids);
            }

            return [];
        });

        $map = [
            [['provider' => 'p', 'code' => 's1'], array_map(static fn($i) => $i['id'], $userList1)],
            [['provider' => 'p', 'code' => 's2'], array_map(static fn($i) => $i['id'], $userList2)]
        ];
        $aggregator = $this->createMock(Aggregator::class);
        $aggregator->method('users')->willReturnMap($map);

        $this->subscribeUsers($mailType, $userList);

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(100, $this->jobQueueCount($batch, $template));

        $expectedUsers = $this->getUserEmailsByKeys($userList1, array_keys($userList1));
        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing($expectedUsers, $actualUsers);
    }

    public function testIncludeExcludeSegments()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $job = $this->createJob(
            includeSegments: [['code' => 's1', 'provider' => 'p'], ['code' => 's2', 'provider' => 'p']],
            excludeSegments: [['code' => 's3', 'provider' => 'p'], ['code' => 's4', 'provider' => 'p']]
        );
        $batch = $this->createBatch($job, $template);

        $includeUserList1 = $this->generateUsers(10);
        $includeUserList2 = $this->generateUsers(5, 11);
        $excludeUserList3 = $this->generateUsers(5);
        $excludeUserList4 = $this->generateUsers(4, 14);

        $userList = array_merge($includeUserList1, $includeUserList2);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, $ids);
            }

            return [];
        });

        $map = [
            [['provider' => 'p', 'code' => 's1'], array_map(static fn($i) => $i['id'], $includeUserList1)],
            [['provider' => 'p', 'code' => 's2'], array_map(static fn($i) => $i['id'], $includeUserList2)],
            [['provider' => 'p', 'code' => 's3'], array_map(static fn($i) => $i['id'], $excludeUserList3)],
            [['provider' => 'p', 'code' => 's4'], array_map(static fn($i) => $i['id'], $excludeUserList4)]
        ];
        $aggregator = $this->createMock(Aggregator::class);
        $aggregator->method('users')->willReturnMap($map);

        $this->subscribeUsers($mailType, array_merge($includeUserList1, $includeUserList2));

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(8, $this->jobQueueCount($batch, $template));

        $expectedUsers = $this->getUserEmailsByIds($userList, [6,7,8,9,10,11,12,13]);
        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing($expectedUsers, $actualUsers);
    }

    public function testFilteringUnsubscribed()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $batch = $this->createJobAndBatch($template);

        $userList = $this->generateUsers(100);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, array_flip($ids));
            }

            return [];
        });

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        $subscribedUserKeys = array_rand($userList, 6);
        foreach ($subscribedUserKeys as $key) {
            $this->subscribeUsers($mailType, [$userList[$key]]);
        }

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        // keep only subscribed users
        $this->assertEquals(6, $this->jobQueueCount($batch, $template));

        $expectedUsers = $this->getUserEmailsByKeys($userList, $subscribedUserKeys);
        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing($expectedUsers, $actualUsers);
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

        $userList = $this->generateUsers(100);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, array_flip($ids));
            }

            return [];
        });

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        $mailVariantUserKeys = [];
        // Split user subscription into two variants
        foreach ($userList as $i => $item) {
            if ($i % 2) {
                $mailVariantUserKeys[] = $i;
                $variantId = $mailTypeVariant1->id;
            } else {
                $variantId = $mailTypeVariant2->id;
            }

            $this->userSubscriptionsRepository->subscribeUser(
                $mailType,
                $item['id'],
                $item['email'],
                $variantId
            );
        }

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(50, $this->jobQueueCount($batch, $template));

        $expectedUsers = $this->getUserEmailsByKeys($userList, $mailVariantUserKeys);
        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing($expectedUsers, $actualUsers);
    }

    public function testFilteringAlreadySentContext()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $context = 'context';
        $batch = $this->createJobAndBatch($template, null, $context);

        $userList = $this->generateUsers(100);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, array_flip($ids));
            }

            return [];
        });

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        $generator = $this->getGenerator($aggregator, $userProvider);

        // subscribe users
        $this->subscribeUsers($mailType, $userList);

        // Simulate some users have already received the same email (create mail_logs entries with same context)
        foreach (array_rand($userList, 7) as $key) {
            $this->mailLogsRepository->add(
                $userList[$key]['email'],
                $template->subject,
                $template->id,
                $batch->mail_job_id,
                $batch->id,
                null,
                null,
                $context
            );
            unset($userList[$key]);
        }

        // Simulate already queued in another batch with same context
        $anotherBatch = $this->createBatch($batch->job, $template);
        $insert = [];
        foreach (array_rand($userList, 10) as $key) {
            $user = $userList[$key];
            $insert[] = [
                'batch' => $anotherBatch->id,
                'templateId' => $template->id,
                'email' => $user['email'],
                'sorting' => rand(), /** @phpstan-ignore-line */
                'context' => $anotherBatch->mail_job->context,
            ];
            unset($userList[$key]);
        }
        $this->jobQueueRepository->multiInsert($insert);

        $userMap = [];
        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(83, $this->jobQueueCount($batch, $template));

        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user['email'], $userList), $actualUsers);
    }

    public function testFilteringAlreadyQueued()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $batch = $this->createJobAndBatch($template);

        $userList = $this->generateUsers(100);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, array_flip($ids));
            }

            return [];
        });

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        // subscribe users
        $this->subscribeUsers($mailType, $userList);

        $anotherBatch = $this->createBatch($batch->job, $template);
        $insert = [];
        foreach (array_rand($userList, 15) as $key) {
            $user = $userList[$key];
            $insert[] = [
                'batch' => $anotherBatch->id,
                'templateId' => $template->id,
                'email' => $user['email'],
                'sorting' => rand(), /** @phpstan-ignore-line */
                'context' => null,
            ];
            unset($userList[$key]);
        }
        $this->jobQueueRepository->multiInsert($insert);

        // Test generator
        $generator = $this->getGenerator($aggregator, $userProvider);

        $userMap = [];

        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(85, $this->jobQueueCount($batch, $template));

        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user['email'], $userList), $actualUsers);
    }

    public function testFilteringAlreadySent()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $batch = $this->createJobAndBatch($template);

        $userList = $this->generateUsers(100);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, array_flip($ids));
            }

            return [];
        });

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        $generator = $this->getGenerator($aggregator, $userProvider);

        // subscribe users
        $this->subscribeUsers($mailType, $userList);

        // Simulate some users have already received the same email (create mail_logs entries)
        foreach (array_rand($userList, 7) as $key) {
            $user = $userList[$key];
            $this->mailLogsRepository->add(
                $user['email'],
                $template->subject,
                $template->id,
                $batch->mail_job_id,
                $batch->id,
            );
            unset($userList[$key]);
        }

        $userMap = [];
        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        $this->assertEquals(93, $this->jobQueueCount($batch, $template));

        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user['email'], $userList), $actualUsers);
    }

    public function testFilteringStripEmails()
    {
        // Prepare data
        $mailType = $this->createMailTypeWithCategory();

        $layout = $this->createMailLayout();
        $template = $this->createTemplate($layout, $mailType);
        $batch = $this->createJobAndBatch($template, null, null, 35);

        $userList = $this->generateUsers(50000);

        $userProvider = $this->createMock(IUser::class);
        $userProvider->method('list')->willReturnCallback(function ($ids, $page) use ($userList) {
            if ($page === 1) {
                return array_intersect_key($userList, array_flip($ids));
            }

            return [];
        });

        $aggregator = $this->createConfiguredMock(Aggregator::class, [
            'users' => array_keys($userList)
        ]);

        $generator = $this->getGenerator($aggregator, $userProvider);

        // subscribe users
        $this->subscribeUsers($mailType, $userList);

        $userMap = [];
        $generator->insertUsersIntoJobQueue($batch, $userMap);
        $generator->filterQueue($batch);

        // max emails count set on batch
        $this->assertEquals(35, $this->jobQueueCount($batch, $template));
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

        $actualUsers = $this->jobQueueRepository->getBatchEmails($batch1)->fetchPairs(null, 'email');
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user['email'], $subscribedUsers1), $actualUsers);


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

    private function subscribeUsers($mailType, $users): void
    {
        foreach (array_chunk($users, 1000, true) as $usersChunk) {
            $insert = [];
            foreach ($usersChunk as $user) {
                $insert[] = [
                    'user_id' => $user['id'],
                    'user_email' => $user['email'],
                    'mail_type_id' => $mailType->id,
                    'created_at' => new DateTime(),
                    'updated_at' => new DateTime(),
                    'subscribed' => true,
                ];
            }
            $this->database->query("INSERT INTO mail_user_subscriptions", $insert);
        }
    }

    private function getUserEmailsByKeys($users, $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $users[$key]['email'];
        }

        return $result;
    }

    private function getUserEmailsByIds($users, $ids): array
    {
        return array_map(
            static function ($user) {
                return $user['email'];
            },
            array_filter($users, static function ($user) use ($ids) {
                return in_array($user['id'], $ids, true);
            })
        );
    }
}
