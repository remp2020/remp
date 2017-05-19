<?php

use Phinx\Migration\AbstractMigration;

class CreateListTable extends AbstractMigration
{
    public function change()
    {
        $this->table('lists')
            ->addColumn('code', 'string')
            ->addColumn('name', 'string')
            ->addColumn('description', 'text')
            ->addColumn('order', 'integer')
            ->addColumn('is_consent_required', 'boolean', ['default' => false])
            ->addColumn('is_locked', 'boolean', ['default' => true])
            ->addColumn('is_public', 'boolean', ['default' => true])
            ->addTimestamps()
            ->save();

        $this->table('list_user_consents')
            ->addColumn('user_id', 'integer')
            ->addColumn('list_id', 'integer')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->addForeignKey('list_id', 'lists', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->save();
    }
}
