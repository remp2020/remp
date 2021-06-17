<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailUserSubscriptionRenameUtmToRtm extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_user_subscriptions')
            ->renameColumn('utm_source', 'rtm_source')
            ->renameColumn('utm_medium', 'rtm_medium')
            ->renameColumn('utm_campaign', 'rtm_campaign')
            ->renameColumn('utm_content', 'rtm_content')
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_user_subscriptions')
            ->renameColumn('rtm_source', 'utm_source')
            ->renameColumn('rtm_medium', 'utm_medium')
            ->renameColumn('rtm_campaign', 'utm_campaign')
            ->renameColumn('rtm_content', 'utm_content')
            ->update();
    }
}
