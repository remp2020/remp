<?php

use Phinx\Migration\AbstractMigration;

class FixingHermesColumnName extends AbstractMigration
{
    public function change()
    {
        $this->table('hermes_tasks')
            ->renameColumn('execute_at', 'processed_at')
            ->update();
    }
}
