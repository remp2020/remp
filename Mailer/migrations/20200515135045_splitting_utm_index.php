<?php

use Phinx\Migration\AbstractMigration;

class SplittingUtmIndex extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_user_subscriptions')
            ->removeIndex(['utm_campaign', 'utm_content'])
            ->addIndex('utm_campaign')
            ->addIndex('utm_content')
            ->update();
    }
}
