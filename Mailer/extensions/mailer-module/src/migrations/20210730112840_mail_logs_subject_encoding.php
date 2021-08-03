<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailLogsSubjectEncoding extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET foreign_key_checks = 0');

        // first, create new column; this could take some time, but it's not blocking
        $this->execute('
            ALTER TABLE mail_logs
            ADD COLUMN subject_new varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL AFTER subject,
            ALGORITHM=INPLACE,
            LOCK=NONE
        ');

        // rename old column, should be instant
        $this->execute('
            ALTER TABLE mail_logs
            CHANGE COLUMN subject subject_old varchar(255) DEFAULT NULL,
            ALGORITHM=INPLACE,
            LOCK=NONE'
        );

        // rename new column, should be instant
        $this->execute('
            ALTER TABLE mail_logs
            CHANGE COLUMN subject_new subject varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            ALGORITHM=INPLACE,
            LOCK=NONE'
        );

        // restore subjects in the new column; these updates are blocking new writes so we do them in batches

        $result = $this->fetchRow("SELECT MAX(id), MIN(id) FROM mail_logs");
        $maxId = $result[0];
        $minId = $result[1];

        if ($maxId !== null && $minId !== null) {
            $current = $maxId;
            $step = 100000;

            $this->output->write("Migrating subjects to the new column: ");
            while ($current >= $minId) {
                $this->output->write(".");
                $low = $current - $step;

                $this->execute("
                UPDATE mail_logs
                SET subject = subject_old
                WHERE id <= {$current} AND id >= {$low} AND subject IS NULL
            ");

                $current -= $step;
            }
            $this->output->writeln(' OK!');
        }

        // drop old column (non-blocking)
        $this->execute('
            ALTER TABLE mail_logs
            DROP COLUMN subject_old,
            ALGORITHM=INPLACE,
            LOCK=NONE'
        );
        $this->execute('SET foreign_key_checks = 1');
    }

    public function down(): void
    {
        $this->output->writeln("DOWN migration not possible due to complexity of the migration");
    }
}
