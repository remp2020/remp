<?php

use Phinx\Migration\AbstractMigration;

class CreateMailTemplatesAggregatedStats extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_templates_aggregated_data')
            ->addColumn('mail_template_id', 'integer')
            ->addColumn('date', 'date')
            ->addColumn('sent', 'integer')
            ->addColumn('delivered', 'integer')
            ->addColumn('opened', 'integer')
            ->addColumn('clicked', 'integer')
            ->addColumn('dropped', 'integer')
            ->addColumn('spam_complained', 'integer')
            ->create();
    }

    public function down()
    {
        $this->table('mail_templates_aggregated_data')
            ->drop();
    }
}
