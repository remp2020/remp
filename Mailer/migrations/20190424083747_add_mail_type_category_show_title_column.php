<?php

use Phinx\Migration\AbstractMigration;

class AddMailTypeCategoryShowTitleColumn extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_type_categories')
            ->addColumn('show_title', 'boolean', [
                'null' => false,
                'default' => true,
            ])
            ->update();
    }
}
