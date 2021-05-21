<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

final class ConvertToUtf8mb4 extends AbstractMigration
{
    public function up()
    {
        $sql = <<<SQL
ALTER DATABASE
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
        
ALTER TABLE autologin_tokens ROW_FORMAT=DYNAMIC;
ALTER TABLE
    autologin_tokens
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE configs ROW_FORMAT=DYNAMIC;
ALTER TABLE
    configs
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
ALTER TABLE configs
MODIFY COLUMN value TEXT,
MODIFY COLUMN description TEXT;

ALTER TABLE hermes_tasks ROW_FORMAT=DYNAMIC;
ALTER TABLE
    hermes_tasks
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_job_batch ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_job_batch
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_job_batch_templates ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_job_batch_templates
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_job_queue ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_job_queue
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_jobs ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_jobs
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_layouts ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_layouts
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_log_conversions ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_log_conversions
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_snippets ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_snippets
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_source_template ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_source_template
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_template_stats ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_template_stats
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_templates ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_templates
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_type_categories ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_type_categories
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_type_stats ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_type_stats
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_type_variants ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_type_variants
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_types ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_types
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
ALTER TABLE mail_types
MODIFY COLUMN description TEXT;

ALTER TABLE mail_user_subscription_variants ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_user_subscription_variants
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE mail_user_subscriptions ROW_FORMAT=DYNAMIC;
ALTER TABLE
    mail_user_subscriptions
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE phinxlog ROW_FORMAT=DYNAMIC;
ALTER TABLE
    phinxlog
    CONVERT TO CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
SQL;

        $this->execute($sql);
    }

    public function down()
    {
        $this->output->writeln('Down migration is not possible.');
        throw new IrreversibleMigrationException();
    }
}
