<?php

use Phinx\Migration\AbstractMigration;

class MailUserSubscriptionMeta extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_user_subscriptions')
            ->addColumn('utm_source', 'string', ['null' => true])
            ->addColumn('utm_medium', 'string', ['null' => true])
            ->addColumn('utm_campaign', 'string', ['null' => true])
            ->addColumn('utm_content', 'string', ['null' => true])
            ->addIndex(['utm_campaign', 'utm_content'])
            ->save();
    }
}
