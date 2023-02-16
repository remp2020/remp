<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNewMailUserSubscriptionsAndMailUserSubscriptionVariantsTables extends AbstractMigration
{
    public function up(): void
    {
        $mailUserSubscriptionsRowCount = $this->query('SELECT 1 FROM mail_user_subscriptions LIMIT 1;')->fetch();

        if ($mailUserSubscriptionsRowCount === false) {
            $this->table('mail_user_subscription_variants')
                ->dropForeignKey('mail_user_subscription_id')
                ->save();

            $this->table('mail_user_subscription_variants')
                ->changeColumn('mail_user_subscription_id', 'biginteger')
                ->save();

            $this->table('mail_user_subscriptions')
                ->changeColumn('id', 'biginteger', ['identity' => true])
                ->save();

            $this->table('mail_user_subscription_variants')
                ->addForeignKey('mail_user_subscription_id', 'mail_user_subscriptions')
                ->save();
        } else {
            $this->query("
                CREATE TABLE mail_user_subscriptions_v2 LIKE mail_user_subscriptions;
                CREATE TABLE mail_user_subscription_variants_v2 LIKE mail_user_subscription_variants;
            ");

            $this->table('mail_user_subscription_variants_v2')
                ->changeColumn('mail_user_subscription_id', 'biginteger')
                ->save();

            $this->table('mail_user_subscriptions_v2')
                ->changeColumn('id', 'biginteger', ['identity' => true])
                ->addForeignKey('mail_type_id', 'mail_types')
                ->save();

            $this->table('mail_user_subscription_variants_v2')
                ->addForeignKey('mail_user_subscription_id', 'mail_user_subscriptions_v2')
                ->addForeignKey('mail_type_variant_id', 'mail_type_variants')
                ->save();
        }
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available.');
    }
}
