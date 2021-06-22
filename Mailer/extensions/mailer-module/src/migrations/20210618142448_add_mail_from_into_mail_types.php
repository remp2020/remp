<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMailFromIntoMailTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_types')
            ->addColumn('mail_from', 'string', ['after' => 'mailer_alias', 'null' => true])
            ->update();
    }
}
