<?php

use Phinx\Migration\AbstractMigration;

class AddingHermesTasksTable extends AbstractMigration
{
    public function change()
    {
        if (!$this->hasTable('hermes_tasks')) {
            $this->table('hermes_tasks', ['id' => false])
                ->addColumn('id', 'string', ['null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('type', 'string', ['null' => false])
                ->addColumn('payload', 'text', ['null' => false])
                ->addColumn('execute_at', 'datetime', ['null' => false])
                ->addColumn('state', 'string', ['null' => false])
                ->addIndex('execute_at')
                ->addIndex('id', ['unique' => true])
                ->create();
        }
    }
}
