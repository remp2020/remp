<?php
declare(strict_types=1);

namespace Tests\Feature;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Commands\AggregateMailTemplateStatsCommand;
use Remp\MailerModule\Commands\ProcessJobStatsCommand;
use Remp\MailerModule\Repositories\MailLogsStatsStateRepository;
use Remp\MailerModule\Repositories\MailTemplateDirectStatsRepository;
use Remp\MailerModule\Repositories\MailTemplateStatsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Covers the branch's core promise: reported mail stats must not change when old mail_logs
 * rows are removed (partition pruning, or any other kind of mail_logs deletion).
 */
class StatsPruneInvarianceTest extends BaseFeatureTestCase
{
    private AggregateMailTemplateStatsCommand $aggregateMailTemplateStatsCommand;

    private ProcessJobStatsCommand $processJobStatsCommand;

    private MailTemplateStatsRepository $mailTemplateStatsRepository;

    private MailTemplateDirectStatsRepository $mailTemplateDirectStatsRepository;

    private MailLogsStatsStateRepository $mailLogsStatsStateRepository;

    private ActiveRow $template;

    private DateTime $today;

    private int $emailCounter = 0;

    public function setUp(): void
    {
        parent::setUp();

        $this->aggregateMailTemplateStatsCommand = $this->inject(AggregateMailTemplateStatsCommand::class);
        $this->processJobStatsCommand = $this->inject(ProcessJobStatsCommand::class);
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

        $this->today = (new DateTime())->setTime(0, 0);
    }

    // -----------------------------------------------------------------
    // B1 — direct-send display invariance (MailTemplateDirectStatsRepository)
    // -----------------------------------------------------------------

    public static function directSendInvarianceDataProvider(): array
    {
        return [
            'SinglePastDayPlusToday' => [
                'pastSends' => [
                    ['daysAgo' => 1, 'delivered' => true, 'opened' => true, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => true],
                    ['daysAgo' => 1, 'delivered' => true, 'opened' => false, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                ],
                'todaySends' => [
                    ['delivered' => true, 'opened' => true, 'clicked' => true, 'dropped' => false, 'spam' => false, 'converted' => false],
                ],
                'expectedTotal' => ['sent' => 3, 'delivered' => 3, 'opened' => 2, 'clicked' => 1, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 1],
            ],
            'MultiplePastDaysPlusToday' => [
                'pastSends' => [
                    ['daysAgo' => 1, 'delivered' => true, 'opened' => false, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                    ['daysAgo' => 3, 'delivered' => true, 'opened' => true, 'clicked' => true, 'dropped' => false, 'spam' => false, 'converted' => true],
                    ['daysAgo' => 3, 'delivered' => false, 'opened' => false, 'clicked' => false, 'dropped' => true, 'spam' => false, 'converted' => false],
                ],
                'todaySends' => [
                    ['delivered' => true, 'opened' => false, 'clicked' => false, 'dropped' => false, 'spam' => true, 'converted' => false],
                    ['delivered' => true, 'opened' => true, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                ],
                'expectedTotal' => ['sent' => 5, 'delivered' => 4, 'opened' => 2, 'clicked' => 1, 'dropped' => 1, 'spam_complained' => 1, 'converted' => 1],
            ],
            'OnlyPastRollup_NoLiveSendsToday' => [
                'pastSends' => [
                    ['daysAgo' => 2, 'delivered' => true, 'opened' => true, 'clicked' => false, 'dropped' => false, 'spam' => false, 'converted' => false],
                ],
                'todaySends' => [],
                'expectedTotal' => ['sent' => 1, 'delivered' => 1, 'opened' => 1, 'clicked' => 0, 'dropped' => 0, 'spam_complained' => 0, 'converted' => 0],
            ],
            'OnlyTodaySends_NoPastRollupYet' => [
                'pastSends' => [],
                'todaySends' => [
                    ['delivered' => false, 'opened' => false, 'clicked' => false, 'dropped' => true, 'spam' => false, 'converted' => false],
                ],
                'expectedTotal' => ['sent' => 1, 'delivered' => 0, 'opened' => 0, 'clicked' => 0, 'dropped' => 1, 'spam_complained' => 0, 'converted' => 0],
            ],
        ];
    }

    #[DataProvider('directSendInvarianceDataProvider')]
    public function testDirectSendStatsInvariantAcrossPruning(array $pastSends, array $todaySends, array $expectedTotal): void
    {
        foreach ($pastSends as $send) {
            $createdAt = (clone $this->today)->modify("-{$send['daysAgo']} days");
            $this->seedDirectMailLog($createdAt, $send['delivered'], $send['opened'], $send['clicked'], $send['dropped'], $send['spam'], $send['converted']);
        }

        foreach ($todaySends as $send) {
            $this->seedDirectMailLog(clone $this->today, $send['delivered'], $send['opened'], $send['clicked'], $send['dropped'], $send['spam'], $send['converted']);
        }

        // Roll every day touched above — past days plus "today" — into mail_template_direct_stats.
        // This mirrors how the real cron now runs mail:aggregate-mail-template-stats every
        // minute, aggregating "today" too, so the persisted rollup is the only place direct-send
        // stats are read from (no more live mail_logs read for "today").
        $daysAgoUsed = array_unique(array_merge(array_column($pastSends, 'daysAgo'), [0]));
        foreach ($daysAgoUsed as $daysAgo) {
            $dateArg = (clone $this->today)->modify("-{$daysAgo} days")->format('Y-m-d');
            $result = $this->aggregateMailTemplateStatsCommand->run(new StringInput("--date={$dateArg}"), new NullOutput());
            $this->assertSame(Command::SUCCESS, $result);
        }

        $before = $this->mailTemplateDirectStatsRepository->sumForTemplates([$this->template->id]);
        $this->assertStatsMatch($expectedTotal, $before);

        // Remove the past-day mail_logs (+ their conversions) — exactly the kind of removal
        // partition pruning performs. Today's rows are deliberately left untouched, since
        // "today" is never pruned.
        $pastLogIds = $this->mailLogsRepository->getTable()
            ->where('mail_template_id', $this->template->id)
            ->where('created_at <', $this->today)
            ->fetchPairs(null, 'id');
        if ($pastLogIds) {
            $this->mailLogConversionsRepository->deleteForMailLogs($pastLogIds);
            $this->mailLogsRepository->getTable()->where('id', $pastLogIds)->delete();
        }

        $after = $this->mailTemplateDirectStatsRepository->sumForTemplates([$this->template->id]);
        $this->assertStatsMatch($expectedTotal, $after);
        $this->assertSame($before, $after, 'stats must not change after removing already-aggregated past mail_logs');
    }

    private function assertStatsMatch(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $actual[$key], "Mismatch on '{$key}'");
        }
    }

    private function seedDirectMailLog(
        \DateTimeInterface $createdAt,
        bool $delivered,
        bool $opened,
        bool $clicked,
        bool $dropped,
        bool $spamComplained,
        bool $converted,
    ): void {
        $data = $this->mailLogsRepository->getInsertData(
            email: 'direct' . (++$this->emailCounter) . '@example.com',
            subject: 'subject',
            templateId: $this->template->id,
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
            $this->mailLogConversionsRepository->upsert($log, DateTime::from($createdAt));
        }
    }

    // -----------------------------------------------------------------
    // B2 — batch seal invariance (mail:job-stats / mail_job_batch_templates)
    // -----------------------------------------------------------------

    public function testBatchStatsSealInvariance(): void
    {
        $oldDate = (clone $this->today)->modify('-60 days');
        $cutoffDate = (clone $this->today)->modify('-30 days');
        $recentDate = (clone $this->today)->modify('-5 days');

        // --- Control: no cutoff date active yet. Deleting a batch's mail_logs and re-running
        // mail:job-stats DOES zero its counters — the baseline behaviour the cutoff date
        // exists to protect against below.
        $controlBatch = $this->createBatchWithLogs($oldDate, delivered: 3, converted: 1);
        $this->runJobStatsCommand();
        $controlBefore = $this->fetchBatchTemplateStats($controlBatch);
        $this->assertSame(3, (int) $controlBefore->delivered);
        $this->assertSame(1, (int) $controlBefore->converted);

        $this->deleteBatchMailLogs($controlBatch);
        $this->runJobStatsCommand();
        $controlAfter = $this->fetchBatchTemplateStats($controlBatch);
        $this->assertSame(0, (int) $controlAfter->delivered, 'without an active cutoff date, deletion must zero the counters');
        $this->assertSame(0, (int) $controlAfter->converted);

        // --- Sealed: a batch whose first_email_sent_at predates the cutoff date keeps its
        // last-known-good counters after its mail_logs are pruned.
        $sealedBatch = $this->createBatchWithLogs($oldDate, delivered: 4, converted: 2);
        $this->runJobStatsCommand();
        $sealedBefore = $this->fetchBatchTemplateStats($sealedBatch);
        $this->assertSame(4, (int) $sealedBefore->delivered);
        $this->assertSame(2, (int) $sealedBefore->converted);

        $this->mailLogsStatsStateRepository->raiseCutoffDateTo($cutoffDate);

        $this->deleteBatchMailLogs($sealedBatch);
        $this->runJobStatsCommand();
        $sealedAfter = $this->fetchBatchTemplateStats($sealedBatch);
        $this->assertSame(4, (int) $sealedAfter->delivered, 'a batch older than the cutoff date must keep its counters after its mail_logs are pruned');
        $this->assertSame(2, (int) $sealedAfter->converted);

        // --- Still recomputed: a batch whose first_email_sent_at is after the cutoff date is
        // not sealed, so deleting its mail_logs still zeroes its counters on the next run.
        $recomputedBatch = $this->createBatchWithLogs($recentDate, delivered: 5, converted: 3);
        $this->runJobStatsCommand();
        $recomputedBefore = $this->fetchBatchTemplateStats($recomputedBatch);
        $this->assertSame(5, (int) $recomputedBefore->delivered);
        $this->assertSame(3, (int) $recomputedBefore->converted);

        $this->deleteBatchMailLogs($recomputedBatch);
        $this->runJobStatsCommand();
        $recomputedAfter = $this->fetchBatchTemplateStats($recomputedBatch);
        $this->assertSame(0, (int) $recomputedAfter->delivered, 'a batch after the cutoff date is not sealed and must still be recomputed');
        $this->assertSame(0, (int) $recomputedAfter->converted);
    }

    private function createBatchWithLogs(\DateTimeInterface $firstEmailSentAt, int $delivered, int $converted): ActiveRow
    {
        $batch = $this->createJobAndBatch($this->template);
        $this->batchesRepository->update($batch, ['first_email_sent_at' => $firstEmailSentAt]);

        for ($i = 0; $i < $delivered; $i++) {
            $data = $this->mailLogsRepository->getInsertData(
                email: 'batch' . (++$this->emailCounter) . '@example.com',
                subject: 'subject',
                templateId: $this->template->id,
                jobId: $batch->mail_job_id,
                batchId: $batch->id,
            );
            $data['created_at'] = $firstEmailSentAt;
            $data['updated_at'] = $firstEmailSentAt;
            $data['delivered_at'] = $firstEmailSentAt;
            $log = $this->mailLogsRepository->insert($data);

            if ($i < $converted) {
                $this->mailLogConversionsRepository->upsert($log, DateTime::from($firstEmailSentAt));
            }
        }

        return $batch;
    }

    private function runJobStatsCommand(): void
    {
        $result = $this->processJobStatsCommand->run(new StringInput(''), new NullOutput());
        $this->assertSame(Command::SUCCESS, $result);
    }

    private function fetchBatchTemplateStats(ActiveRow $batch): ActiveRow
    {
        return $this->batchTemplatesRepository->findByBatchId($batch->id)->fetch();
    }

    private function deleteBatchMailLogs(ActiveRow $batch): void
    {
        $logIds = $this->mailLogsRepository->getTable()->where('mail_job_batch_id', $batch->id)->fetchPairs(null, 'id');
        if ($logIds) {
            $this->mailLogConversionsRepository->deleteForMailLogs($logIds);
            $this->mailLogsRepository->getTable()->where('id', $logIds)->delete();
        }
    }

    // -----------------------------------------------------------------
    // B3 — cutoff date lifecycle (MailLogsStatsStateRepository)
    // -----------------------------------------------------------------

    public static function cutoffDateLifecycleDataProvider(): array
    {
        return [
            'InitCutoffDate_FromNull_Adopts' => [
                'startingCutoff' => null, 'operation' => 'initCutoffDate', 'argument' => '2026-06-01', 'expectedCutoff' => '2026-06-01',
            ],
            'InitCutoffDate_AlreadySet_NoOp' => [
                'startingCutoff' => '2026-05-01', 'operation' => 'initCutoffDate', 'argument' => '2026-06-01', 'expectedCutoff' => '2026-05-01',
            ],
            'RaiseCutoffDateTo_FromNull_Adopts' => [
                'startingCutoff' => null, 'operation' => 'raiseCutoffDateTo', 'argument' => '2026-06-01', 'expectedCutoff' => '2026-06-01',
            ],
            'RaiseCutoffDateTo_Later_Moves' => [
                'startingCutoff' => '2026-05-01', 'operation' => 'raiseCutoffDateTo', 'argument' => '2026-06-01', 'expectedCutoff' => '2026-06-01',
            ],
            'RaiseCutoffDateTo_Earlier_NoOp' => [
                'startingCutoff' => '2026-06-01', 'operation' => 'raiseCutoffDateTo', 'argument' => '2026-05-01', 'expectedCutoff' => '2026-06-01',
            ],
            'LowerCutoffDateTo_FromNull_NoOp' => [
                'startingCutoff' => null, 'operation' => 'lowerCutoffDateTo', 'argument' => '2026-06-01', 'expectedCutoff' => null,
            ],
            'LowerCutoffDateTo_Earlier_Moves' => [
                'startingCutoff' => '2026-06-01', 'operation' => 'lowerCutoffDateTo', 'argument' => '2026-05-01', 'expectedCutoff' => '2026-05-01',
            ],
            'LowerCutoffDateTo_Later_NoOp' => [
                'startingCutoff' => '2026-05-01', 'operation' => 'lowerCutoffDateTo', 'argument' => '2026-06-01', 'expectedCutoff' => '2026-05-01',
            ],
        ];
    }

    #[DataProvider('cutoffDateLifecycleDataProvider')]
    public function testCutoffDateLifecycle(?string $startingCutoff, string $operation, string $argument, ?string $expectedCutoff): void
    {
        $this->seedCutoffDate($startingCutoff !== null ? new DateTime($startingCutoff) : null);

        match ($operation) {
            'initCutoffDate' => $this->mailLogsStatsStateRepository->initCutoffDate(new DateTime($argument)),
            'raiseCutoffDateTo' => $this->mailLogsStatsStateRepository->raiseCutoffDateTo(new DateTime($argument)),
            'lowerCutoffDateTo' => $this->mailLogsStatsStateRepository->lowerCutoffDateTo(new DateTime($argument)),
        };

        $cutoffDate = $this->mailLogsStatsStateRepository->getCutoffDate();
        if ($expectedCutoff === null) {
            $this->assertNull($cutoffDate);
        } else {
            $this->assertNotNull($cutoffDate);
            $this->assertSame($expectedCutoff, $cutoffDate->format('Y-m-d'));
        }
    }

    /** Seeds the single-row cutoff date state directly, bypassing initCutoffDate()/raiseCutoffDateTo() under test. */
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
}
