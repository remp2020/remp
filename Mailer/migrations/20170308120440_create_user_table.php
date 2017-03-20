<?php

use Phinx\Migration\AbstractMigration;

class CreateUserTable extends AbstractMigration
{
    public function change()
    {
        $this->table('users')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('email', 'string')
            ->addIndex('email', ['unique' => true])
            ->save();
    }
}
