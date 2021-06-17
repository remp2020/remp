<?php

use Phinx\Migration\AbstractMigration;

class UnifyingCollateOnMailTemplates extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_templates')
            ->changeColumn('code', 'string', ['null' => false, 'after' => 'name', 'collation' => 'utf8mb4_unicode_ci'])
            ->save();
    }

    public function down()
    {
        $this->table('mail_templates')
            ->changeColumn('code', 'string', ['null' => false, 'after' => 'name', 'collation' => 'utf8_general_ci'])
            ->save();
    }
}
