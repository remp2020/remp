<?php

use Phinx\Migration\AbstractMigration;

class CreateListTable extends AbstractMigration
{
    public function change()
    {
        $this->table('lists')
            ->addColumn('name', 'string')
            ->addTimestamps()
            ->addColumn('consent_required', 'boolean', ['default' => true])
            ->save();

        $this->table('list_user_consents')
            ->addColumn('user_id', 'integer')
            ->addColumn('list_id', 'integer')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id', 'list_id'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->addForeignKey('list_id', 'lists', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->save();
    }
}
