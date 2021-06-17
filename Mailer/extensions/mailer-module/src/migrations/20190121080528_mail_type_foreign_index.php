<?php

use Phinx\Migration\AbstractMigration;

class MailTypeForeignIndex extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_user_subscriptions')
            ->addForeignKey('mail_type_id', 'mail_types')
            ->save();
    }
}
