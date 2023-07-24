<?php

namespace Tests\Feature\Commands;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Commands\UnsubscribeInactiveUsersCommand;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Models\Segment\ISegment;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\Feature\BaseFeatureTestCase;
use Tests\Feature\TestSegmentProvider;

class UnsubscribeInactiveUsersCommandTest extends BaseFeatureTestCase
{
    protected ISegment $testSegmentProvider;

    protected Aggregator $segmentAggregator;

    protected UnsubscribeInactiveUsersCommand $unsubscribeInactiveUsersCommand;

    protected ActiveRow $mailLayout;

    private array $user = [
        'id' => 1,
        'email' => 'user1@example.com',
    ];

    private array $mailTypes = [
        [
            'code' => 'system',
            'name' => 'System',
        ],
        [
            'code' => 'test',
            'name' => 'Test',
        ],
        [
            'code' => 'test-omit',
            'name' => 'Test OMIT'
        ]
    ];

    private ActiveRow $listCategory;

    public function setUp(): void
    {
        parent::setUp();

        $this->testSegmentProvider = $this->inject(TestSegmentProvider::class);
        $this->testSegmentProvider->testUsers = [$this->user['id']];

        $this->segmentAggregator = $this->inject(Aggregator::class);
        $this->segmentAggregator->register($this->testSegmentProvider);

        $this->unsubscribeInactiveUsersCommand = $this->inject(UnsubscribeInactiveUsersCommand::class);

        $this->mailLayout = $this->createMailLayout();

        $this->listCategory = $this->listCategoriesRepository->add('Test lists category', 'test-lists-category', 1);
        foreach ($this->mailTypes as $mailType) {
            $list = $this->listsRepository->add(
                $this->listCategory->id,
                1,
                $mailType['code'],
                $mailType['name'],
                1,
                false,
                false,
                ''
            );

            $this->templatesRepository->add($mailType['name'], $mailType['code'], '', 'test@example.com', '', '', '', $this->mailLayout->id, $list->id);
        }
    }

    public function dataProvider()
    {
        return [
            'TooLittleDeliveredEmails_ShouldNotUnsubscribe' => [
                'subscribe' => ['system', 'test'],
                'logs' => [
                    ['delivered' => '-11 days', 'opened' => null],
                    ['delivered' => '-10 days', 'opened' => null],
                    ['delivered' => '-9 days', 'opened' => null],
                ],
                'result' => [
                    'system' => true,
                    'test' => true,
                ],
            ],
            'TooManyNotOpenedEmails_ShouldUnsubscribe' => [
                'subscribe' => ['system', 'test', 'test-omit'],
                'logs' => [
                      ['delivered' => '-11 days', 'opened' => null],
                      ['delivered' => '-10 days', 'opened' => null],
                      ['delivered' => '-9 days', 'opened' => null],
                      ['delivered' => '-8 days', 'opened' => null],
                      ['delivered' => '-7 days', 'opened' => null],
                      ['delivered' => '-6 days', 'opened' => null],
                ],
                'result' => [
                    'system' => true,
                    'test' => false,
                    'test-omit' => false,
                ],
            ],
            'TooManyNotOpenedEmails_WithOmit_ShouldUnsubscribe' => [
                'subscribe' => ['system', 'test', 'test-omit'],
                'logs' => [
                      ['delivered' => '-11 days', 'opened' => null],
                      ['delivered' => '-10 days', 'opened' => null],
                      ['delivered' => '-9 days', 'opened' => null],
                      ['delivered' => '-8 days', 'opened' => null],
                      ['delivered' => '-7 days', 'opened' => null],
                      ['delivered' => '-6 days', 'opened' => null],
                ],
                'result' => [
                    'system' => true,
                    'test' => false,
                    'test-omit' => true,
                ],
                'omit' => ['test-omit'],
            ],
            'TooManyNotOpenedEmails_DryRun_ShouldNotUnsubscribe' => [
                'subscribe' => ['system', 'test'],
                'logs' => [
                      ['delivered' => '-11 days', 'opened' => null],
                      ['delivered' => '-10 days', 'opened' => null],
                      ['delivered' => '-9 days', 'opened' => null],
                      ['delivered' => '-8 days', 'opened' => null],
                      ['delivered' => '-7 days', 'opened' => null],
                      ['delivered' => '-6 days', 'opened' => null],
                ],
                'result' => [
                    'system' => true,
                    'test' => true,
                ],
                'omit' => [],
                'dryRun' => true,
            ],
            'SomeOpenedDeliveries_ShouldNotUnsubscribe' => [
                'subscribe' => ['system', 'test'],
                'logs' => [
                      ['delivered' => '-11 days', 'opened' => null],
                      ['delivered' => '-10 days', 'opened' => null],
                      ['delivered' => '-9 days', 'opened' => null],
                      ['delivered' => '-8 days', 'opened' => '-5 days'],
                      ['delivered' => '-7 days', 'opened' => null],
                      ['delivered' => '-6 days', 'opened' => null],
                ],
                'result' => [
                    'system' => true,
                    'test' => true,
                ],
            ],
            'NotMatchedDeliveryThresholdWithinSelectedPeriod_ShouldNotUnsubscribe' => [
                'subscribe' => ['system', 'test', 'test-omit'],
                'logs' => [
                    ['delivered' => '-11 days', 'opened' => null],
                    ['delivered' => '-10 days', 'opened' => null],
                    ['delivered' => '-9 days', 'opened' => null],
                    ['delivered' => '-8 days', 'opened' => null],
                    ['delivered' => '-7 days', 'opened' => null],
                    ['delivered' => '-6 days', 'opened' => null],
                ],
                'result' => [
                    'system' => true,
                    'test' => true,
                    'test-omit' => true,
                ],
                'omit' => [],
                'dryRun' => false,
                'days' => 9,
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUnsubscribeInactive(array $subscribe, array $logs, array $result, array $omit = [], bool $dryRun = false, int $days = null)
    {
        foreach ($subscribe as $mailTypeCode) {
            $this->susbcribeUser($this->user, $mailTypeCode);
        }

        foreach ($logs as $log) {
            $template = $this->templatesRepository->findBy('code', 'test');
            $delivered = DateTime::from($log['delivered']);
            $opened = $log['opened'] ? DateTime::from($log['opened']) : null;
            $this->addMailLog($this->user, $template, $delivered, $opened);
        }

        $input = '--segment-provider ' . TestSegmentProvider::PROVIDER_ALIAS;
        if ($dryRun) {
            $input .= ' --dry-run';
        }
        foreach ($omit as $mailTypeCode) {
            $input .= " --omit-mail-type-code {$mailTypeCode}";
        }
        if ($days) {
            $input .= " --days {$days}";
        }
        $stringInput = new StringInput($input);

        $this->unsubscribeInactiveUsersCommand->run($stringInput, new NullOutput());

        foreach ($result as $mailTypeCode => $shouldBeSubscribed) {
            $mailType = $this->listsRepository->findByCode($mailTypeCode)->fetch();
            $isSubscribed = $this->userSubscriptionsRepository->isUserSubscribed($this->user['id'], $mailType->id);
            $this->assertEquals($shouldBeSubscribed, $isSubscribed);
        }
    }

    private function susbcribeUser(array $user, string $mailTypeCode)
    {
        $list = $this->listsRepository->findByCode($mailTypeCode)->fetch();
        $this->userSubscriptionsRepository->subscribeUser($list, $user['id'], $user['email']);
    }

    private function addMailLog(array $user, ActiveRow $template, DateTime $deliveredAt, ?DateTime $openedAt = null)
    {
        $mailLog = $this->mailLogsRepository->add(
            email: $user['email'],
            subject: 'subject',
            templateId: $template->id,
            userId: $user['id'],
        );

        $this->mailLogsRepository->update($mailLog, [
            'delivered_at' => $deliveredAt,
            'opened_at' => $openedAt
        ]);
    }
}
