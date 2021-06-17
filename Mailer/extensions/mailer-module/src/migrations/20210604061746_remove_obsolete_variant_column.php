<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveObsoleteVariantColumn extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_user_subscriptions')
            ->dropForeignKey('remove_mail_type_variant_id')
            ->save();

        $this->table('mail_user_subscriptions')
            ->removeColumn('remove_mail_type_variant_id')
            ->save();
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available, up migration was destructive.');
    }
}
