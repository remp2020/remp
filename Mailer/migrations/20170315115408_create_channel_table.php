<?php

use Phinx\Migration\AbstractMigration;

class CreateChannelTable extends AbstractMigration
{
    public function change()
    {
        $this->table('channels')
            ->addColumn('name', 'string')
            ->addTimestamps()
            ->addColumn('consent_required', 'boolean', ['default' => true])
            ->save();
    }
}
