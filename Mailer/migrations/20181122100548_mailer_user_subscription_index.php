<?php

use Phinx\Migration\AbstractMigration;

class MailerUserSubscriptionIndex extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_user_subscriptions')
            ->dropForeignKey('mail_type_id')
            ->save();

        $this->table('mail_user_subscriptions')
            ->removeIndex(['mail_type_id'])
            ->addIndex(['mail_type_id', 'subscribed'])
            ->update();
    }

    public function down()
    {
        $this->table('mail_user_subscriptions')
            ->removeIndex(['mail_type_id', 'subscribed'])
            ->addIndex(['mail_type_id'])
            ->update();

        $this->table('mail_user_subscriptions')
            ->addForeignKey('mail_type_id', 'mail_types', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE'
            ])->save();
    }
}
