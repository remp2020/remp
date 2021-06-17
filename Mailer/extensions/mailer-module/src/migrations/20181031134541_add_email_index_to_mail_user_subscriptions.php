<?php

use Phinx\Migration\AbstractMigration;

class AddEmailIndexToMailUserSubscriptions extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_user_subscriptions')
            ->addIndex(['user_email'])
            ->update();
    }
}
