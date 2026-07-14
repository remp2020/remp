<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMailTemplateDirectStatsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_template_direct_stats')
            ->addColumn('mail_template_id', 'integer')
            ->addColumn('date', 'date')
            ->addColumn('sent', 'integer')
            ->addColumn('delivered', 'integer')
            ->addColumn('opened', 'integer')
            ->addColumn('clicked', 'integer')
            ->addColumn('converted', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('dropped', 'integer')
            ->addColumn('spam_complained', 'integer')
            ->addForeignKey('mail_template_id', 'mail_templates')
            ->addIndex(['date', 'mail_template_id', 'sent'])
            ->create();

        $this->table('mail_template_stats')
            ->addColumn('converted', 'integer', ['null' => false, 'default' => 0, 'after' => 'clicked'])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_template_stats')
            ->removeColumn('converted')
            ->update();

        $this->table('mail_template_direct_stats')
            ->drop()
            ->update();
    }
}
