<?php

use Phinx\Migration\AbstractMigration;

class MailTemplateStatsAddTemplatesForeignKey extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_template_stats')
            ->addForeignKey('mail_template_id', 'mail_templates')
            ->save();
    }

    public function down()
    {
        $this->table('mail_template_stats')
            ->dropForeignKey('mail_template_id')
            ->save();
    }
}
