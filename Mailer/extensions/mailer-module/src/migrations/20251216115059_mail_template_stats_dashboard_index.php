<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailTemplateStatsDashboardIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_template_stats')
            ->addIndex(['date', 'mail_template_id', 'sent'])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_template_stats')
            ->removeIndex(['date', 'mail_template_id', 'sent'])
            ->update();
    }
}
