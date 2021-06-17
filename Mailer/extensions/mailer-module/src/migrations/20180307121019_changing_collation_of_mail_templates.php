<?php

use Phinx\Migration\AbstractMigration;

class ChangingCollationOfMailTemplates extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_templates')
            ->changeColumn('name', 'string', ['null' => false, 'after' => 'id', 'collation' => 'utf8mb4_unicode_ci'])
            ->changeColumn('description', 'string', ['null' => false, 'after' => 'code', 'collation' => 'utf8mb4_unicode_ci'])
            ->changeColumn('subject', 'string', ['null' => false, 'after' => 'from', 'collation' => 'utf8mb4_unicode_ci'])
            ->changeColumn('mail_body_text', 'text', ['null' => false, 'after' => 'subject', 'collation' => 'utf8mb4_unicode_ci', 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
            ->changeColumn('mail_body_html', 'text', ['null' => false, 'after' => 'mail_body_text', 'collation' => 'utf8mb4_unicode_ci', 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
            ->save();
    }

    public function down()
    {
        $this->table('mail_templates')
            ->changeColumn('name', 'string', ['null' => false, 'after' => 'id', 'collation' => 'utf8mb4_general_ci'])
            ->changeColumn('description', 'string', ['null' => false, 'after' => 'code', 'collation' => 'utf8_general_ci'])
            ->changeColumn('subject', 'string', ['null' => true, 'after' => 'from', 'collation' => 'utf8mb4_general_ci'])
            ->changeColumn('mail_body_text', 'text', ['null' => false, 'after' => 'subject', 'collation' => 'utf8_general_ci', 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
            ->changeColumn('mail_body_html', 'text', ['null' => false, 'after' => 'mail_body_text', 'collation' => 'utf8_general_ci', 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
            ->save();
    }
}
