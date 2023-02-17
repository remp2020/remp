<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UniqueConfigsName extends AbstractMigration
{
    public function change(): void
    {
        $this->table('configs')
            ->addIndex('name', ['unique' => true])
            ->update();
    }
}
