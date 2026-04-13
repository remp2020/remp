<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeUserIdNullableInMailUserSubscriptions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<SQL
            ALTER TABLE mail_user_subscriptions
            MODIFY user_id INT NULL,
            ALGORITHM=INPLACE, 
            LOCK=NONE;
SQL
        );
    }

    public function down(): void
    {
        $this->execute(<<<SQL
            ALTER TABLE mail_user_subscriptions
            MODIFY user_id INT NOT NULL,
            ALGORITHM=INPLACE, 
            LOCK=NONE;
SQL
        );
    }
}
