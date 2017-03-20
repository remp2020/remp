<?php

use Phinx\Migration\AbstractMigration;

class CreateNewsletterTable extends AbstractMigration
{
    public function change()
    {
        $this->table('newsletters')
            ->addColumn('name', 'string')
            ->addTimestamps()
            ->addColumn('consent_required', 'boolean', ['default' => true])
            ->save();

        $this->table('newsletter_consents')
            ->addColumn('user_id', 'integer')
            ->addColumn('newsletter_id', 'integer')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id', 'newsletter_id'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->addForeignKey('newsletter_id', 'newsletters', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->save();
    }
}
