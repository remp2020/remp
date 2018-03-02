<?php


use Phinx\Migration\AbstractMigration;

class MailUserSubscriptionTypes extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_user_subscription_variants')
            ->addColumn('mail_user_subscription_id', 'integer', ['null' => false])
            ->addColumn('mail_type_variant_id', 'integer', ['null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addForeignKey('mail_user_subscription_id', 'mail_user_subscriptions')
            ->addForeignKey('mail_type_variant_id', 'mail_type_variants')
            ->addIndex(['mail_user_subscription_id', 'mail_type_variant_id'], ['unique' => true])
            ->create();

        $query = "INSERT INTO mail_user_subscription_variants (mail_user_subscription_id, mail_type_variant_id, created_at)
          SELECT id,mail_type_variant_id,NOW() FROM mail_user_subscriptions WHERE mail_type_variant_id IS NOT NULL";
        $this->query($query);

        $this->table('mail_user_subscriptions')
            ->renameColumn('mail_type_variant_id', 'remove_mail_type_variant_id')
            ->update();

        $this->table('mail_types')
            ->addColumn('is_multi_variant', 'boolean', ['default' => false, 'null' => false, 'after' => 'auto_subscribe'])
            ->update();
    }
}
