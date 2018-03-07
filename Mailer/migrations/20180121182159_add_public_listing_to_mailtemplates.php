<?php


use Phinx\Migration\AbstractMigration;

class AddPublicListingToMailtemplates extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_types')
            ->addColumn('public_listing', 'boolean', ['null' => false, 'default' => true, 'after' => 'is_public'])
            ->update();
    }
}
