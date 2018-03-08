<?php

use Phinx\Migration\AbstractMigration;

class MailTemplatesEncodingFix extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_templates')
            ->changeColumn('name', 'string', ['null' => false, 'after' => 'id', 'encoding' => 'utf8mb4'])
            ->changeColumn('description', 'text', ['null' => false, 'after' => 'code', 'encoding' => 'utf8mb4'])
            ->save();
    }

    public function down()
    {
        $this->table('mail_templates')
            ->changeColumn('description', 'string', ['null' => false, 'after' => 'code', 'encoding' => 'utf8'])
            ->changeColumn('name', 'string', ['null' => false, 'after' => 'id', 'encoding' => 'utf8'])
            ->save();
    }
}
