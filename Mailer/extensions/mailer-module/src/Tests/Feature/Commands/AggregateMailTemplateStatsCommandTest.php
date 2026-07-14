<?php
declare(strict_types=1);

namespace Tests\Feature\Commands;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Commands\AggregateMailTemplateStatsCommand;
use Remp\MailerModule\Repositories\MailLogsStatsStateRepository;
use Remp\MailerModule\Repositories\MailTemplateDirectStatsRepository;
use Remp\MailerModule\Repositories\MailTemplateStatsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\Feature\BaseFeatureTestCase;

class AggregateMailTemplateStatsCommandTest extends BaseFeatureTestCase
{
    private AggregateMailTemplateStatsCommand $aggregateMailTemplateStatsCommand;

    private MailTemplateStatsRepository $mailTemplateStatsRepository;

    private MailTemplateDirectStatsRepository $mailTemplateDirectStatsRepository;

    private MailLogsStatsStateRepository $mailLogsStatsStateRepository;

    private ActiveRow $template;

    private ActiveRow $batch;

    private DateTime $windowStart;

    private DateTime $windowEnd;

    private int $emailCounter = 0;

    public function setUp(): void
    {
        parent::setUp();

        $this->aggregateMailTemplateStatsCommand = $this->inject(AggregateMailTemplateStatsCommand::class);
        $this->mailTemplateStatsRepository = $this->inject(MailTemplateStatsRepository::class);
        $this->mailTemplateDirectStatsRepository = $this->inject(MailTemplateDirectStatsRepository::class);
        $this->mailLogsStatsStateRepository = $this->inject(MailLogsStatsStateRepository::class);

        // BaseFeatureTestCase::truncate() does not clean these three tables.
        $this->truncate($this->mailTemplateStatsRepository);
        $this->truncate($this->mailTemplateDirectStatsRepository);
        $this->truncate($this->mailLogsStatsStateRepository);

        $mailLayout = $this->createMailLayout();
        $mailType = $this->createMailTypeWithCategory();
        $this->template = $this->createTemplate($mailLayout, $mailType);
        $this->batch = $this->createJobAndBatch($this->template);

        $this->windowEnd = new DateTime('2026-07-10');
        $this->windowStart = (clone $this->windowEnd)->modify('-1 day');
    }

    public static function dataProvider(): array
    {
        return [
            'AllBatchSends_NoDirectRow' => [
                'sends' => [
                    ['type' => 'batch', 'delivered' => true, 'opened' => true, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => true],
                    ['type' => 'batch', 'delivered' => true, 'opened' => false, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                ],
                'expectedAll' => ['sent' => 2, 'delivered' => 2, 'opened' => 1, 'clicked' => 0, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 1],
                'expectedDirect' => null,
            ],
            'AllDirectSends_DirectRowMatchesAll' => [
                'sends' => [
                    ['type' => 'direct', 'delivered' => true, 'opened' => true, 'clicked' => true, 'dropped' => false, 'spam' => false, 'converted' => true],
                    ['type' => 'direct', 'delivered' => true, 'opened' => false, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                ],
                'expectedAll' => ['sent' => 2, 'delivered' => 2, 'opened' => 1, 'clicked' => 1, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 1],
                'expectedDirect' => ['sent' => 2, 'delivered' => 2, 'opened' => 1, 'clicked' => 1, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 1],
            ],
            'MixedBatchAndDirect_DirectRowOnlyDirectPortion' => [
                'sends' => [
                    // Batch sends: mostly opened/clicked/converted — if the direct rollup
                    // ever leaked these in (the bug this test guards against), the assertion
                    // on 'expectedDirect' below would fail.
                    ['type' => 'batch', 'delivered' => true, 'opened' => true, 'clicked' => true, 'dropped' => false, 'spam' => false, 'converted' => true],
                    ['type' => 'batch', 'delivered' => true, 'opened' => true, 'clicked' => true, 'dropped' => false, 'spam' => false, 'converted' => true],
                    ['type' => 'batch', 'delivered' => true, 'opened' => true, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                    // Direct sends: only one delivered, none opened/clicked/converted.
                    ['type' => 'direct', 'delivered' => true, 'opened' => false, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                    ['type' => 'direct', 'delivered' => false, 'opened' => false, 'clicked' => false, 'dropped' => true, 'spam' => false, 'converted' => false],
                ],
                'expectedAll' => ['sent' => 5, 'delivered' => 4, 'opened' => 3, 'clicked' => 2, 'dropped' => 1, 'spam_complained' => 0, 'converted' => 2],
                'expectedDirect' => ['sent' => 2, 'delivered' => 1, 'opened' => 0, 'clicked' => 0, 'dropped' => 1, 'spam_complained' => 0, 'converted' => 0],
            ],
            'DroppedAndSpamComplained_CountedSeparately' => [
                'sends' => [
                    ['type' => 'direct', 'delivered' => false, 'opened' => false, 'clicked' => false, 'dropped' => true, 'spam' => false, 'converted' => false],
                    ['type' => 'direct', 'delivered' => true, 'opened' => false, 'clicked' => false, 'dropped' => false, 'spam' => true, 'converted' => false],
                    ['type' => 'batch', 'delivered' => false, 'opened' => false, 'clicked' => false, 'dropped' => true, 'spam' => false, 'converted' => false],
                ],
                'expectedAll' => ['sent' => 3, 'delivered' => 1, 'opened' => 0, 'clicked' => 0, 'dropped' => 2, 'spam_complained' => 1, 'converted' => 0],
                'expectedDirect' => ['sent' => 2, 'delivered' => 1, 'opened' => 0, 'clicked' => 0, 'dropped' => 1, 'spam_complained' => 1, 'converted' => 0],
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testAggregate(array $sends, array $expectedAll, ?array $expectedDirect): void
    {
        foreach ($sends as $send) {
            $this->seedMailLog(
                isBatch: $send['type'] === 'batch',
                delivered: $send['delivered'],
                opened: $send['opened'],
                clicked: $send['clicked'],
                dropped: $send['dropped'],
                spamComplained: $send['spam'],
                converted: $send['converted'],
            );
        }

        $result = $this->aggregateMailTemplateStatsCommand->run(
            new StringInput('--date=' . $this->windowStart->format('Y-m-d')),
            new NullOutput()
        );
        $this->assertSame(Command::SUCCESS, $result);

        $allRow = $this->mailTemplateStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $this->windowStart->format('Y-m-d')])
            ->fetch();
        $this->assertNotNull($allRow, 'mail_template_stats row should have been created');
        $this->assertStatsMatch($expectedAll, $allRow);

        $directRow = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $this->windowStart->format('Y-m-d')])
            ->fetch();

        if ($expectedDirect === null) {
            $this->assertNull($directRow, 'mail_template_direct_stats row should not exist for template with only batch sends');
        } else {
            $this->assertNotNull($directRow, 'mail_template_direct_stats row should have been created');
            $this->assertStatsMatch($expectedDirect, $directRow);
        }
    }

    public function testRerunOverwritesRatherThanDuplicates(): void
    {
        $this->seedMailLog(isBatch: false, delivered: true, opened: false, clicked: false, dropped: false, spamComplained: false, converted: false);

        $this->aggregateMailTemplateStatsCommand->run(new StringInput('--date=' . $this->windowStart->format('Y-m-d')), new NullOutput());

        // A second direct send arrives for the same window, then the command re-runs
        // for the same date (e.g. a manual backfill re-run).
        $this->seedMailLog(isBatch: false, delivered: true, opened: true, clicked: false, dropped: false, spamComplained: false, converted: false);

        $this->aggregateMailTemplateStatsCommand->run(new StringInput('--date=' . $this->windowStart->format('Y-m-d')), new NullOutput());

        $this->assertSame(
            1,
            $this->mailTemplateDirectStatsRepository->getTable()
                ->where(['mail_template_id' => $this->template->id, 'date' => $this->windowStart->format('Y-m-d')])
                ->count('*'),
            'upsert should update the existing row, not insert a duplicate'
        );

        $directRow = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $this->windowStart->format('Y-m-d')])
            ->fetch();
        $this->assertStatsMatch(['sent' => 2, 'delivered' => 2, 'opened' => 1, 'clicked' => 0, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 0], $directRow);
    }

    private function assertStatsMatch(array $expected, ActiveRow $row): void
    {
        foreach ($expected as $key => $value) {
            $this->assertSame($value, (int) $row->$key, "Mismatch on '{$key}'");
        }
    }

    private function seedMailLog(
        bool $isBatch,
        bool $delivered,
        bool $opened,
        bool $clicked,
        bool $dropped,
        bool $spamComplained,
        bool $converted,
        ?DateTime $createdAt = null,
    ): void {
        $createdAt = $createdAt ?? $this->windowStart;

        $data = $this->mailLogsRepository->getInsertData(
            email: 'user' . (++$this->emailCounter) . '@example.com',
            subject: 'subject',
            templateId: $this->template->id,
            jobId: $isBatch ? $this->batch->mail_job_id : null,
            batchId: $isBatch ? $this->batch->id : null,
        );
        $data['created_at'] = $createdAt;
        $data['updated_at'] = $createdAt;
        $data['delivered_at'] = $delivered ? $createdAt : null;
        $data['opened_at'] = $opened ? $createdAt : null;
        $data['clicked_at'] = $clicked ? $createdAt : null;
        $data['dropped_at'] = $dropped ? $createdAt : null;
        $data['spam_complained_at'] = $spamComplained ? $createdAt : null;

        $log = $this->mailLogsRepository->insert($data);

        if ($converted) {
            $this->mailLogConversionsRepository->upsert($log, $createdAt);
        }
    }

    /** Seeds the single-row cutoff date state directly, bypassing initCutoffDate()/raiseCutoffDateTo(). */
    private function seedCutoffDate(?\DateTimeInterface $date): void
    {
        $this->database->query('DELETE FROM mail_logs_stats_state');
        if ($date !== null) {
            $this->mailLogsStatsStateRepository->getTable()->insert([
                'id' => 1,
                'cutoff_date' => $date,
                'updated_at' => new DateTime(),
            ]);
        }
    }

    public function testDefaultAggregatesYesterdayAndToday(): void
    {
        $yesterday = (new DateTime('yesterday'))->setTime(0, 0);
        $today = (new DateTime('today'))->setTime(0, 0);

        $this->seedMailLog(isBatch: false, delivered: true, opened: false, clicked: false, dropped: false, spamComplained: false, converted: false, createdAt: $yesterday);
        $this->seedMailLog(isBatch: false, delivered: true, opened: true, clicked: false, dropped: false, spamComplained: false, converted: false, createdAt: $today);

        $result = $this->aggregateMailTemplateStatsCommand->run(new StringInput(''), new NullOutput());
        $this->assertSame(Command::SUCCESS, $result);

        $yesterdayRow = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $yesterday->format('Y-m-d')])
            ->fetch();
        $this->assertNotNull($yesterdayRow, 'the no-option default should aggregate yesterday');
        $this->assertStatsMatch(['sent' => 1, 'delivered' => 1, 'opened' => 0, 'clicked' => 0, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 0], $yesterdayRow);

        $todayRow = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $today->format('Y-m-d')])
            ->fetch();
        $this->assertNotNull($todayRow, 'the no-option default should aggregate today');
        $this->assertStatsMatch(['sent' => 1, 'delivered' => 1, 'opened' => 1, 'clicked' => 0, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 0], $todayRow);
    }

    public function testFromIsClampedToCutoffDate(): void
    {
        $cutoffDate = (clone $this->windowStart)->modify('-5 days');
        $beforeCutoff = (clone $cutoffDate)->modify('-2 days');
        $this->seedCutoffDate($cutoffDate);

        // A row that sits before the cutoff — pruned in a real installation, so it must
        // not be (re)aggregated by a --from reaching back that far.
        $this->seedMailLog(isBatch: false, delivered: true, opened: true, clicked: false, dropped: false, spamComplained: false, converted: false, createdAt: $beforeCutoff);
        // A row on the cutoff date itself, which must still be aggregated.
        $this->seedMailLog(isBatch: false, delivered: true, opened: false, clicked: false, dropped: false, spamComplained: false, converted: false, createdAt: clone $cutoffDate);

        $result = $this->aggregateMailTemplateStatsCommand->run(
            new StringInput('--from=' . $beforeCutoff->format('Y-m-d')),
            new NullOutput()
        );
        $this->assertSame(Command::SUCCESS, $result);

        $beforeCutoffRow = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $beforeCutoff->format('Y-m-d')])
            ->fetch();
        $this->assertNull($beforeCutoffRow, '--from must not reach below the active cutoff date');

        $cutoffRow = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $cutoffDate->format('Y-m-d')])
            ->fetch();
        $this->assertNotNull($cutoffRow, '--from must still cover the cutoff date itself');
    }

    public function testDateBeforeCutoffIsRefusedWithoutForce(): void
    {
        $cutoffDate = (clone $this->windowStart)->modify('-5 days');
        $beforeCutoff = (clone $cutoffDate)->modify('-2 days');
        $this->seedCutoffDate($cutoffDate);

        $this->seedMailLog(isBatch: false, delivered: true, opened: true, clicked: false, dropped: false, spamComplained: false, converted: false, createdAt: $beforeCutoff);

        $result = $this->aggregateMailTemplateStatsCommand->run(
            new StringInput('--date=' . $beforeCutoff->format('Y-m-d')),
            new NullOutput()
        );
        $this->assertSame(Command::FAILURE, $result);

        $row = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $beforeCutoff->format('Y-m-d')])
            ->fetch();
        $this->assertNull($row, '--date below the cutoff must be refused, not silently skipped');
    }

    public function testForceBypassesCutoffClampAndRefusal(): void
    {
        $cutoffDate = (clone $this->windowStart)->modify('-5 days');
        $beforeCutoff = (clone $cutoffDate)->modify('-2 days');
        $this->seedCutoffDate($cutoffDate);

        $this->seedMailLog(isBatch: false, delivered: true, opened: true, clicked: false, dropped: false, spamComplained: false, converted: false, createdAt: $beforeCutoff);

        $result = $this->aggregateMailTemplateStatsCommand->run(
            new StringInput('--date=' . $beforeCutoff->format('Y-m-d') . ' --force'),
            new NullOutput()
        );
        $this->assertSame(Command::SUCCESS, $result);

        $row = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $beforeCutoff->format('Y-m-d')])
            ->fetch();
        $this->assertNotNull($row, '--force should let --date reach below the cutoff date');
    }

    public function testNoCutoffDoesNotClampHistoricalFrom(): void
    {
        $this->seedCutoffDate(null);

        $oldDate = (clone $this->windowStart)->modify('-2 years');
        $this->seedMailLog(isBatch: false, delivered: true, opened: true, clicked: false, dropped: false, spamComplained: false, converted: false, createdAt: $oldDate);

        $result = $this->aggregateMailTemplateStatsCommand->run(
            new StringInput('--date=' . $oldDate->format('Y-m-d')),
            new NullOutput()
        );
        $this->assertSame(Command::SUCCESS, $result);

        $row = $this->mailTemplateDirectStatsRepository->getTable()
            ->where(['mail_template_id' => $this->template->id, 'date' => $oldDate->format('Y-m-d')])
            ->fetch();
        $this->assertNotNull($row, 'with no cutoff active, historical dates must not be clamped/refused');
    }
}
