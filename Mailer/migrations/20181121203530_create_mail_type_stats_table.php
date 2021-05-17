<?php

use Phinx\Migration\AbstractMigration;

class CreateMailTypeStatsTable extends AbstractMigration
{
    public function up()
    {
        $mailTypeStats = $this->table('mail_type_stats');
        $mailTypeStats
            ->addColumn('mail_type_id', 'integer')
            ->addColumn('created_at', 'datetime')
            ->addColumn('subscribers_count', 'integer', [
                'signed' => false
            ])
            ->save();
    }

    public function down()
    {
        $this->table('mail_type_stats')->drop()->save();
    }
}
