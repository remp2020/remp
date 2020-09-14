<?php

use Phinx\Migration\AbstractMigration;

class TemplateClickTrackingFlag extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_templates')
            ->addColumn('click_tracking', 'boolean', ['null' => true])
            ->update();
    }
}
