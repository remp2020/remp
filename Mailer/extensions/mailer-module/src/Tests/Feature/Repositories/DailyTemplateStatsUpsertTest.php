<?php
declare(strict_types=1);

namespace Tests\Feature\Repositories;

use Nette\Utils\DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Repositories\MailTemplateDirectStatsRepository;
use Remp\MailerModule\Repositories\MailTemplateStatsRepository;
use Remp\MailerModule\Repositories\Repository;
use Tests\Feature\BaseFeatureTestCase;

/**
 * Both daily rollups are read as unbounded lifetime sums, so a duplicate
 * (mail_template_id, date) row inflates reported statistics permanently. The recommended
 * schedule (a per-minute run plus a daily trailing --from window) recomputes the same day
 * from two processes by design, so upsert() has to be safe under that on its own —
 * hence the unique key added by AddUniqueKeyToTemplateStatsTables.
 */
class DailyTemplateStatsUpsertTest extends BaseFeatureTestCase
{
    /**
     * @return array<string, array{class-string<Repository>}>
     */
    public static function repositoryProvider(): array
    {
        return [
            'batch rollup' => [MailTemplateStatsRepository::class],
            'direct-send rollup' => [MailTemplateDirectStatsRepository::class],
        ];
    }

    #[DataProvider('repositoryProvider')]
    public function testRepeatedUpsertKeepsASingleRowWithTheLatestValues(string $repositoryClass): void
    {
        $repository = $this->inject($repositoryClass);
        $this->truncate($repository);

        $template = $this->createTemplate($this->createMailLayout(), $this->createMailTypeWithCategory());
        $date = new DateTime('2026-08-05 00:00:00');

        $repository->upsert($date, $template->id, 10, 9, 8, 7, 6, 5, 4);
        $repository->upsert($date, $template->id, 20, 19, 18, 17, 16, 15, 14);

        $rows = $repository->getTable()
            ->where('mail_template_id', $template->id)
            ->where('date', $date->format('Y-m-d'))
            ->fetchAll();

        $this->assertCount(1, $rows, 'The same (mail_template_id, date) must never produce a second row.');

        $row = reset($rows);
        $this->assertSame(20, $row->sent);
        $this->assertSame(19, $row->delivered);
        $this->assertSame(18, $row->opened);
        $this->assertSame(17, $row->clicked);
        $this->assertSame(16, $row->dropped);
        $this->assertSame(15, $row->spam_complained);
        $this->assertSame(14, $row->converted);
    }

    #[DataProvider('repositoryProvider')]
    public function testUpsertKeepsDaysAndTemplatesApart(string $repositoryClass): void
    {
        $repository = $this->inject($repositoryClass);
        $this->truncate($repository);

        $layout = $this->createMailLayout();
        $mailType = $this->createMailTypeWithCategory();
        $templateA = $this->createTemplate($layout, $mailType);
        $templateB = $this->createTemplate($layout, $mailType);

        $repository->upsert(new DateTime('2026-08-04 00:00:00'), $templateA->id, 1, 0, 0, 0, 0, 0, 0);
        $repository->upsert(new DateTime('2026-08-05 00:00:00'), $templateA->id, 2, 0, 0, 0, 0, 0, 0);
        $repository->upsert(new DateTime('2026-08-05 00:00:00'), $templateB->id, 3, 0, 0, 0, 0, 0, 0);

        $this->assertSame(3, $repository->getTable()->count('*'));
        $this->assertSame(6, (int) $repository->getTable()->sum('sent'));
    }
}
