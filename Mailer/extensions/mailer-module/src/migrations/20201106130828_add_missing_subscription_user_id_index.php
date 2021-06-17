<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMissingSubscriptionUserIdIndex extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_user_subscriptions')
            ->addIndex('user_id')
            ->update();
    }
}
