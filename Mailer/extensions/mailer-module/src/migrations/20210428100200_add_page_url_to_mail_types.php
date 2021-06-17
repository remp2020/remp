<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPageUrlToMailTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_types')
            ->addColumn('page_url','string', ['null' => true, 'after' => 'preview_url'])
            ->update();
    }
}
