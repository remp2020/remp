<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMailTypesForeignKeyMailTypeStats extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_type_stats')
            ->addForeignKey('mail_type_id', 'mail_types', 'id')
            ->update();
    }
}
