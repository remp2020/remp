<?php

use Phinx\Migration\AbstractMigration;

class FixingHermesColumnName extends AbstractMigration
{
    public function change()
    {
        if (!$this->table('hermes_tasks')->hasColumn('execute_at')) {
            $this->table('hermes_tasks')
                ->renameColumn('execute_at', 'processed_at')
                ->update();
        }
    }
}
