<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeletedAtIntoMailTypeVariants extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_type_variants')
            ->addColumn('deleted_at', 'datetime', ['default' => null, 'null' => true, 'after' => 'created_at'])
            ->update();
    }
}