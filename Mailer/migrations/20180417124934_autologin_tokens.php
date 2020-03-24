<?php

use Phinx\Migration\AbstractMigration;

class AutologinTokens extends AbstractMigration
{
    public function change()
    {
        if (!$this->hasTable('autologin_tokens')) {
            $this->table('autologin_tokens')
                ->addColumn('token', 'string')
                ->addColumn('user_id', 'integer', ['null' => true])
                ->addColumn('email', 'string')
                ->addColumn('created_at', 'datetime')
                ->addColumn('valid_from', 'datetime')
                ->addColumn('valid_to', 'datetime')
                ->addColumn('used_count', 'integer', ['default' => 0])
                ->addColumn('max_count', 'integer', ['default' => 1])
                ->addIndex(['token'], ['unique' => true])
                ->addIndex(['valid_to', 'user_id'])
                ->addIndex(['user_id'])
                ->create();
        }
    }
}
