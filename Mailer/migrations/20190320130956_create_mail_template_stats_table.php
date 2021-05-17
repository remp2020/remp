<?php

use Phinx\Migration\AbstractMigration;

class CreateMailTemplateStatsTable extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_template_stats')
            ->addColumn('mail_template_id', 'integer')
            ->addColumn('date', 'date')
            ->addColumn('sent', 'integer')
            ->addColumn('delivered', 'integer')
            ->addColumn('opened', 'integer')
            ->addColumn('clicked', 'integer')
            ->addColumn('dropped', 'integer')
            ->addColumn('spam_complained', 'integer')
            ->addForeignKey('mail_template_id', 'mail_templates')
            ->create();
    }

    public function down()
    {
        $this->table('mail_template_stats')
            ->drop()->save();
    }
}
