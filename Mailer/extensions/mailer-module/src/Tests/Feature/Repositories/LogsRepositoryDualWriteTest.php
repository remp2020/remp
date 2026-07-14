<?php
declare(strict_types=1);

namespace Tests\Feature\Repositories;

use Nette\Utils\DateTime;
use Remp\MailerModule\Commands\MigrateMailLogsToPartitionsCommand;
use Remp\MailerModule\Models\RedisClientFactory;
use Tests\Feature\BaseFeatureTestCase;

/**
 * Covers LogsRepository's dual-write path, which mirrors live writes into the partitioned
 * shadow table while mail_logs:migrate-to-partitions is running.
 *
 * The shape that matters is the one production actually has: the live table still carries
 * `hard_bounced_at` (a leftover on every long-lived installation) while the shadow table
 * deliberately does not, since CreatePartitionedMailLogsTable drops it. Mirroring the raw
 * source row would then fail with "Unknown column 'hard_bounced_at' in 'field list'" on
 * every single send for the whole duration of the migration, so setUp() below reproduces
 * exactly that asymmetry.
 */
class LogsRepositoryDualWriteTest extends BaseFeatureTestCase
{
    /**
     * Hardcoded rather than read from LogsRepository::getNewTable(): building that Selection
     * resolves the table's primary key through Nette's structure, which throws while the table
     * doesn't exist yet. setUp() asserts the two agree once the table is in place.
     */
    private const SHADOW_TABLE = 'mail_logs_partitioned';

    private string $shadowTable = self::SHADOW_TABLE;

    private bool $addedHardBouncedAt = false;

    public function setUp(): void
    {
        parent::setUp();

        if (!$this->mailLogsHasHardBouncedAt()) {
            $this->database->query('ALTER TABLE `mail_logs` ADD COLUMN `hard_bounced_at` datetime DEFAULT NULL');
            $this->addedHardBouncedAt = true;
        }

        $this->database->query("DROP TABLE IF EXISTS `{$this->shadowTable}`");
        $this->database->query("CREATE TABLE `{$this->shadowTable}` LIKE `mail_logs`");
        $this->database->query("ALTER TABLE `{$this->shadowTable}` DROP COLUMN `hard_bounced_at`");

        // Nette's Structure only auto-rebuilds once per request on a lookup miss, so a table
        // created after some earlier miss already consumed that rebuild stays invisible.
        $this->database->getStructure()->rebuild();

        $this->assertSame(
            self::SHADOW_TABLE,
            $this->mailLogsRepository->getNewTable()->getName(),
            'The configured dual-write target changed; update this test.'
        );

        $this->redis()->set(MigrateMailLogsToPartitionsCommand::MAIL_LOGS_PARTITIONS_MIGRATION_IS_RUNNING, (new DateTime())->format(DATE_ATOM));
    }

    protected function tearDown(): void
    {
        // Unconditionally reversed: a leftover flag would make every other test's mail_logs
        // insert try to mirror into a table that no longer exists.
        $this->redis()->del(MigrateMailLogsToPartitionsCommand::MAIL_LOGS_PARTITIONS_MIGRATION_IS_RUNNING);
        $this->database->query("DROP TABLE IF EXISTS `{$this->shadowTable}`");

        if ($this->addedHardBouncedAt) {
            $this->database->query('ALTER TABLE `mail_logs` DROP COLUMN `hard_bounced_at`');
            $this->addedHardBouncedAt = false;
        }

        // Leave no stale structure behind for whichever test the random order runs next.
        $this->database->getStructure()->rebuild();

        parent::tearDown();
    }

    public function testInsertIsMirroredIntoShadowTableWithoutTheRemovedColumn(): void
    {
        $template = $this->createTemplate($this->createMailLayout(), $this->createMailTypeWithCategory());

        $log = $this->mailLogsRepository->add(
            email: 'dual-write@example.com',
            subject: 'SUBJECT',
            templateId: $template->id,
            mailSenderId: 'sender-id-1',
            userId: 123,
        );

        $mirrored = $this->database->fetch("SELECT * FROM `{$this->shadowTable}` WHERE `id` = ?", $log->id);

        $this->assertNotNull($mirrored, 'Insert was not mirrored into the shadow table.');
        $this->assertSame('dual-write@example.com', $mirrored->email);
        $this->assertSame(123, $mirrored->user_id);
        $this->assertSame('sender-id-1', $mirrored->mail_sender_id);
        $this->assertSame($template->id, $mirrored->mail_template_id);
        $this->assertEquals($log->created_at, $mirrored->created_at);
    }

    public function testUpdateIsMirroredIntoShadowTable(): void
    {
        $template = $this->createTemplate($this->createMailLayout(), $this->createMailTypeWithCategory());

        $log = $this->mailLogsRepository->add(
            email: 'dual-write-update@example.com',
            subject: 'SUBJECT',
            templateId: $template->id,
            mailSenderId: 'sender-id-2',
        );

        $deliveredAt = new DateTime('2026-08-05 10:11:12');
        $this->mailLogsRepository->update($log, ['delivered_at' => $deliveredAt]);

        $mirrored = $this->database->fetch("SELECT * FROM `{$this->shadowTable}` WHERE `id` = ?", $log->id);

        $this->assertNotNull($mirrored, 'Insert was not mirrored into the shadow table.');
        $this->assertEquals($deliveredAt, DateTime::from($mirrored->delivered_at));
    }

    private function mailLogsHasHardBouncedAt(): bool
    {
        return $this->database->fetch("SHOW COLUMNS FROM `mail_logs` LIKE 'hard_bounced_at'") !== null;
    }

    private function redis(): \Predis\Client
    {
        // Same client parameters LogsRepository resolves to: config.neon wires
        // setRedisClientFactory() only, leaving the database/prefix defaults untouched.
        return $this->inject(RedisClientFactory::class)->getClient();
    }
}
